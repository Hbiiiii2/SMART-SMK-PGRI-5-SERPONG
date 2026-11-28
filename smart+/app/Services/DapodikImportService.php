<?php

namespace App\Services;

use App\Models\DapodikSyncLog;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaOrangTua;
use App\Models\SiswaPeriodik;
use App\Models\SiswaProfil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DapodikImportService
{
    /**
     * Dry-run preview of a Dapodik Excel/CSV upload without writing to the database.
     */
    public function preview(UploadedFile $file, int $tahunAjaranId, string $semester = 'Ganjil'): array
    {
        $parsed = $this->parseFile($file);
        $anomalies = [];
        $rombels = [];
        $sampleRows = [];
        $newCount = 0;
        $updateCount = 0;
        $skippedCount = 0;

        foreach ($parsed['rows'] as $rowMeta) {
            $rowNumber = $rowMeta['row_number'];
            $normalized = $rowMeta['data'];
            $rowAnomalies = $this->collectRowAnomalies($normalized, $rowNumber);
            foreach ($rowAnomalies as $a) {
                $anomalies[] = $a;
            }

            if (empty($normalized['nama_lengkap'])) {
                $skippedCount++;
                continue;
            }

            if (!empty($normalized['kelas'])) {
                $rombels[$normalized['kelas']] = ($rombels[$normalized['kelas']] ?? 0) + 1;
            }

            $existing = $this->findExistingSiswa($normalized);
            if ($existing) {
                $updateCount++;
            } else {
                $newCount++;
            }

            if (count($sampleRows) < 12) {
                $sampleRows[] = [
                    'row_number' => $rowNumber,
                    'action' => $existing ? 'update' : 'insert',
                    'nama_lengkap' => $normalized['nama_lengkap'],
                    'nipd' => $normalized['nipd'],
                    'nisn' => $normalized['nisn'],
                    'nik' => $normalized['nik'],
                    'kelas' => $normalized['kelas'],
                    'email_pribadi' => $normalized['email_pribadi'],
                    'ibu' => $normalized['ortu']['ibu']['nama'] ?? null,
                    'anomalies' => array_column($rowAnomalies, 'message'),
                ];
            }
        }

        return [
            'filename' => $file->getClientOriginalName(),
            'format' => $parsed['format'],
            'total_rows' => count($parsed['rows']),
            'insert_count' => $newCount,
            'update_count' => $updateCount,
            'skipped_count' => $skippedCount,
            'anomaly_count' => count($anomalies),
            'rombels' => $rombels,
            'sample_rows' => $sampleRows,
            'anomalies' => array_slice($anomalies, 0, 100),
        ];
    }

    /**
     * Execute full non-destructive Dapodik Excel/CSV import and write audit log.
     */
    public function import(
        UploadedFile $file,
        int $tahunAjaranId,
        string $semester = 'Ganjil',
        bool $createAccounts = true,
        ?string $defaultPassword = null,
        ?int $userId = null
    ): DapodikSyncLog {
        $startedAt = now();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $source = $extension === 'csv' ? 'csv' : 'excel';

        $parsed = $this->parseFile($file);
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $anomalies = [];

        foreach ($parsed['rows'] as $rowMeta) {
            $rowNumber = $rowMeta['row_number'];
            $normalized = $rowMeta['data'];

            if (empty($normalized['nama_lengkap'])) {
                $skipped++;
                continue;
            }

            try {
                $result = $this->upsertNormalizedStudent(
                    normalized: $normalized,
                    tahunAjaranId: $tahunAjaranId,
                    semester: $semester,
                    createAccounts: $createAccounts,
                    defaultPassword: $defaultPassword,
                    syncSource: 'import_dapodik',
                    rowNumber: $rowNumber
                );

                if ($result['action'] === 'inserted') {
                    $inserted++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }

                foreach ($result['anomalies'] as $a) {
                    $anomalies[] = $a;
                }
            } catch (\Throwable $e) {
                $errors++;
                $anomalies[] = [
                    'row' => $rowNumber,
                    'nama' => $normalized['nama_lengkap'] ?? '-',
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        CacheService::flushDropdowns();

        $status = $errors > 0 && ($inserted + $updated) === 0
            ? 'failed'
            : ($errors > 0 ? 'partial' : 'success');

        return DapodikSyncLog::create([
            'user_id' => $userId,
            'source' => $source,
            'entity' => 'peserta_didik',
            'filename' => $file->getClientOriginalName(),
            'status' => $status,
            'total_rows' => count($parsed['rows']),
            'inserted_count' => $inserted,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'warning_count' => count(array_filter($anomalies, fn ($a) => ($a['type'] ?? 'warning') === 'warning')),
            'error_count' => $errors,
            'summary_notes' => "Import {$file->getClientOriginalName()} selesai: {$inserted} baru, {$updated} diperbarui, {$skipped} dilewati, {$errors} gagal.",
            'anomalies' => array_slice($anomalies, 0, 200),
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    /**
     * Shared upsert pipeline for both Dapodik Excel/CSV Import and WebService API Sync.
     */
    public function upsertNormalizedStudent(
        array $normalized,
        int $tahunAjaranId,
        string $semester = 'Ganjil',
        bool $createAccounts = false,
        ?string $defaultPassword = null,
        string $syncSource = 'import_dapodik',
        int $rowNumber = 0
    ): array {
        $anomalies = $this->collectRowAnomalies($normalized, $rowNumber);

        return DB::transaction(function () use (
            $normalized,
            $tahunAjaranId,
            $semester,
            $createAccounts,
            $defaultPassword,
            $syncSource,
            $anomalies
        ) {
            $existing = $this->findExistingSiswa($normalized);
            $isNew = $existing === null;

            $statusSiswa = $normalized['status_siswa'] ?? ($existing?->status_siswa ?? 'aktif');
            $kelasName = $normalized['kelas'] ?? $existing?->kelas;
            $rombel = null;

            if (!empty($kelasName) && $statusSiswa === 'aktif') {
                $rombel = Rombel::resolveOrCreateByName($kelasName, $tahunAjaranId, $semester);
                $kelasName = $rombel->nama;
            } elseif (!empty($normalized['dapodik_rombongan_belajar_id'])) {
                $rombel = Rombel::where('dapodik_rombongan_belajar_id', $normalized['dapodik_rombongan_belajar_id'])->first();
                if ($rombel) {
                    $kelasName = $rombel->nama;
                }
            }

            $userId = $existing?->user_id;

            // Optional login account creation (never overwrites existing user_id or users.email)
            if ($createAccounts && $userId === null && $statusSiswa === 'aktif') {
                $loginEmail = $this->generateUniqueStudentLoginEmail($normalized);
                $rawPassword = $defaultPassword ?: ($normalized['nisn'] ?: ($normalized['nipd'] ?: 'smkpgri52026'));
                $user = User::create([
                    'name' => $normalized['nama_lengkap'],
                    'email' => $loginEmail,
                    'password' => Hash::make($rawPassword),
                    'role' => 'siswa',
                    'is_active' => true,
                ]);
                $userId = $user->id;
            }

            $payload = [
                'user_id' => $userId,
                'dapodik_peserta_didik_id' => $normalized['dapodik_peserta_didik_id'] ?? $existing?->dapodik_peserta_didik_id,
                'dapodik_registrasi_id' => $normalized['dapodik_registrasi_id'] ?? $existing?->dapodik_registrasi_id,
                'nipd' => $normalized['nipd'] ?? $existing?->nipd,
                'nisn' => $normalized['nisn'] ?? $existing?->nisn,
                'nik' => $normalized['nik'] ?? $existing?->nik,
                'no_kk' => $normalized['no_kk'] ?? $existing?->no_kk,
                'nama_lengkap' => $normalized['nama_lengkap'],
                'jenis_kelamin' => $normalized['jenis_kelamin'] ?? $existing?->jenis_kelamin,
                'tempat_lahir' => $normalized['tempat_lahir'] ?? $existing?->tempat_lahir,
                'tanggal_lahir' => $normalized['tanggal_lahir'] ?? $existing?->tanggal_lahir,
                'agama' => $normalized['agama'] ?? $existing?->agama,
                'agama_id' => $normalized['agama_id'] ?? $existing?->agama_id,
                'kelas' => $kelasName,
                'rombel_id' => $rombel?->id ?? $existing?->rombel_id,
                'alamat' => $normalized['alamat'] ?? $existing?->alamat,
                'no_telepon' => $normalized['no_telepon'] ?? $existing?->no_telepon,
                'telepon_rumah' => $normalized['telepon_rumah'] ?? $existing?->telepon_rumah,
                'no_wa_aktif' => $existing?->no_wa_aktif, // SMART+ owned: never overwrite from Dapodik
                'email_pribadi' => $normalized['email_pribadi'] ?? $existing?->email_pribadi,
                'tahun_ajaran_id' => $existing?->tahun_ajaran_id ?? $tahunAjaranId,
                'status_siswa' => $statusSiswa,
                'tanggal_masuk' => $normalized['tanggal_masuk'] ?? $existing?->tanggal_masuk,
                'tanggal_keluar' => $normalized['tanggal_keluar'] ?? $existing?->tanggal_keluar,
                'alasan_keluar' => $normalized['alasan_keluar'] ?? $existing?->alasan_keluar,
                'sync_source' => $syncSource,
                'last_synced_at' => now(),
            ];

            if ($isNew) {
                $siswa = Siswa::create($payload);
            } else {
                $existing->update($payload);
                $siswa = $existing->fresh();
            }

            // Upsert 1-to-1 SiswaProfil
            if (!empty($normalized['profil'])) {
                SiswaProfil::updateOrCreate(
                    ['siswa_id' => $siswa->id],
                    $normalized['profil']
                );
            }

            // Upsert 1-to-Many Orang Tua / Wali
            foreach (['ayah', 'ibu', 'wali'] as $hubungan) {
                $ortuData = $normalized['ortu'][$hubungan] ?? null;
                if ($ortuData && $this->hasNonEmptyValues($ortuData)) {
                    SiswaOrangTua::updateOrCreate(
                        ['siswa_id' => $siswa->id, 'hubungan' => $hubungan],
                        $ortuData
                    );
                }
            }

            // Upsert Periodik for active year & semester
            if (!empty($normalized['periodik']) && $this->hasNonEmptyValues($normalized['periodik'])) {
                SiswaPeriodik::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'semester' => $semester,
                    ],
                    $normalized['periodik']
                );
            }

            // Keep RiwayatKelasSiswa in sync
            if (!empty($kelasName)) {
                RiwayatKelasSiswa::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'semester' => $semester,
                    ],
                    [
                        'rombel_id' => $rombel?->id,
                        'dapodik_anggota_rombel_id' => $normalized['dapodik_anggota_rombel_id'] ?? null,
                        'kelas' => $kelasName,
                        'status' => $statusSiswa === 'lulus' ? 'lulus' : ($statusSiswa === 'aktif' ? 'aktif' : 'mutasi'),
                    ]
                );
            }

            return [
                'action' => $isNew ? 'inserted' : 'updated',
                'siswa' => $siswa,
                'anomalies' => $anomalies,
            ];
        });
    }

    protected function findExistingSiswa(array $normalized): ?Siswa
    {
        if (!empty($normalized['dapodik_peserta_didik_id'])) {
            $found = Siswa::where('dapodik_peserta_didik_id', $normalized['dapodik_peserta_didik_id'])->first();
            if ($found) {
                return $found;
            }
        }

        if (!empty($normalized['nisn'])) {
            $found = Siswa::where('nisn', $normalized['nisn'])->first();
            if ($found) {
                return $found;
            }
        }

        if (!empty($normalized['nipd'])) {
            $found = Siswa::where('nipd', $normalized['nipd'])->first();
            if ($found) {
                return $found;
            }
        }

        if (!empty($normalized['nik']) && preg_match('/^\d{16}$/', $normalized['nik']) === 1) {
            $found = Siswa::where('nik', $normalized['nik'])->first();
            if ($found) {
                return $found;
            }
        }

        if (!empty($normalized['nama_lengkap']) && !empty($normalized['tanggal_lahir'])) {
            return Siswa::whereRaw('LOWER(nama_lengkap) = ?', [strtolower($normalized['nama_lengkap'])])
                ->whereDate('tanggal_lahir', $normalized['tanggal_lahir'])
                ->first();
        }

        return null;
    }

    public function generateUniqueStudentLoginEmail(array $normalized, ?int $ignoreUserId = null): string
    {
        $base = $normalized['nipd']
            ?: ($normalized['nisn']
                ?: Str::slug((string) ($normalized['nama_lengkap'] ?? ''), '.'));
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $base);
        $base = trim((string) preg_replace('/\.{2,}/', '.', (string) $base), '.');
        if ($base === '') {
            $base = 'siswa';
        }

        $candidate = strtolower($base) . '@smkpgri5serpong.sch.id';
        $counter = 1;
        while (User::where('email', $candidate)
            ->when($ignoreUserId !== null, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists()) {
            $candidate = strtolower($base) . ".{$counter}@smkpgri5serpong.sch.id";
            $counter++;
        }

        return $candidate;
    }

    /**
     * Parse either Excel (.xlsx/.xls) or CSV (.csv) exported from Dapodik.
     */
    protected function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $rawRows = [];

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, false, false);
            $format = 'Dapodik Excel (' . strtoupper($ext) . ')';
        } else {
            $rawRows = $this->readCsvRows($file->getRealPath());
            $format = 'Dapodik CSV';
        }

        return [
            'format' => $format,
            'rows' => $this->mapDapodikMatrix($rawRows),
        ];
    }

    protected function readCsvRows(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        // Remove UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // If not valid UTF-8, convert from Windows-1252
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        // Detect delimiter (comma vs semicolon) from first 5 lines
        $sampleLines = '';
        for ($i = 0; $i < 5 && !feof($stream); $i++) {
            $sampleLines .= fgets($stream);
        }
        rewind($stream);
        $delimiter = substr_count($sampleLines, ';') > substr_count($sampleLines, ',') ? ';' : ',';

        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Map raw 2D matrix (handling Dapodik's 4 metadata rows + 2-row merged header + footer notes).
     */
    protected function mapDapodikMatrix(array $rawRows): array
    {
        $headerIdx = null;
        foreach ($rawRows as $idx => $row) {
            $cells = array_map(fn ($v) => strtolower(trim((string) $v)), $row);
            if (in_array('nama', $cells, true) && (in_array('nisn', $cells, true) || in_array('nipd', $cells, true))) {
                $headerIdx = $idx;
                break;
            }
        }

        if ($headerIdx === null) {
            return [];
        }

        $headerRow1 = $rawRows[$headerIdx] ?? [];
        $nextRow = $rawRows[$headerIdx + 1] ?? [];
        $nextRowLower = array_map(fn ($v) => strtolower(trim((string) $v)), $nextRow);

        // Detect Dapodik 2-row merged header where row 2 has "Tahun Lahir", "Jenjang Pendidikan", "Pekerjaan"
        $hasSecondHeaderRow = in_array('tahun lahir', $nextRowLower, true) && in_array('jenjang pendidikan', $nextRowLower, true);
        $dataStartIdx = $hasSecondHeaderRow ? $headerIdx + 2 : $headerIdx + 1;

        $colMap = $this->buildColumnIndexMap($headerRow1, $hasSecondHeaderRow ? $nextRow : []);

        $mapped = [];
        $total = count($rawRows);
        for ($i = $dataStartIdx; $i < $total; $i++) {
            $row = $rawRows[$i];
            if (!is_array($row)) {
                continue;
            }

            $nonEmpty = array_filter($row, fn ($v) => trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            // Skip footer legend rows like ",,: sudah keluar/mutasi"
            $joined = strtolower(implode(' ', $nonEmpty));
            if (str_contains($joined, 'sudah keluar/mutasi')) {
                continue;
            }

            $nama = $this->cleanText($this->cellValue($row, $colMap['nama'] ?? null));
            if ($nama === null || str_starts_with($nama, ':')) {
                continue;
            }

            $mapped[] = [
                'row_number' => $i + 1,
                'data' => $this->normalizeMappedRow($row, $colMap),
            ];
        }

        return $mapped;
    }

    protected function buildColumnIndexMap(array $row1, array $row2): array
    {
        $map = [];
        $currentGroup = null;
        $maxCols = max(count($row1), count($row2));

        for ($col = 0; $col < $maxCols; $col++) {
            $h1 = $this->normalizeHeaderLabel($row1[$col] ?? '');
            $h2 = $this->normalizeHeaderLabel($row2[$col] ?? '');

            if ($h1 === 'data ayah') {
                $currentGroup = 'ayah';
            } elseif ($h1 === 'data ibu') {
                $currentGroup = 'ibu';
            } elseif ($h1 === 'data wali') {
                $currentGroup = 'wali';
            } elseif ($h1 !== '' && !in_array($h1, ['nama', 'tahun lahir', 'jenjang pendidikan', 'pekerjaan', 'penghasilan', 'nik'], true)) {
                $currentGroup = null;
            }

            if ($currentGroup !== null && $h2 !== '') {
                $key = $currentGroup . '_' . $this->normalizeSubHeader($h2);
                $map[$key] ??= $col;
                continue;
            }

            if ($currentGroup !== null && $h1 !== '' && $h1 !== 'data ' . $currentGroup) {
                $key = $currentGroup . '_' . $this->normalizeSubHeader($h1);
                $map[$key] ??= $col;
                continue;
            }

            $label = $h1 !== '' ? $h1 : $h2;
            if ($label === '') {
                continue;
            }

            $canonical = match ($label) {
                'no' => 'no',
                'rombel saat ini', 'kelas', 'rombel' => 'rombel',
                'nama', 'nama lengkap' => 'nama',
                'nipd', 'nis' => 'nipd',
                'jk', 'jenis kelamin' => 'jk',
                'nisn' => 'nisn',
                'tempat lahir' => 'tempat_lahir',
                'tanggal lahir' => 'tanggal_lahir',
                'nik' => 'nik',
                'agama' => 'agama',
                'alamat' => 'alamat',
                'rt' => 'rt',
                'rw' => 'rw',
                'dusun', 'nama dusun' => 'dusun',
                'kelurahan', 'desa/kelurahan' => 'kelurahan',
                'kecamatan' => 'kecamatan',
                'kode pos' => 'kode_pos',
                'jenis tinggal' => 'jenis_tinggal',
                'alat transportasi' => 'alat_transportasi',
                'telepon' => 'telepon',
                'hp', 'no hp', 'no. hp' => 'hp',
                'e-mail', 'email', 'email pribadi' => 'email',
                'skhun' => 'skhun',
                'penerima kps' => 'penerima_kps',
                'no. kps', 'no kps' => 'no_kps',
                'no peserta ujian nasional' => 'no_peserta_un',
                'no seri ijazah' => 'no_seri_ijazah',
                'penerima kip' => 'penerima_kip',
                'nomor kip' => 'nomor_kip',
                'nama di kip' => 'nama_di_kip',
                'nomor kks' => 'nomor_kks',
                'no registrasi akta lahir' => 'no_registrasi_akta_lahir',
                'bank' => 'bank',
                'nomor rekening bank' => 'nomor_rekening_bank',
                'rekening atas nama' => 'rekening_atas_nama',
                'layak pip (usulan dari sekolah)', 'layak pip' => 'layak_pip',
                'alasan layak pip' => 'alasan_layak_pip',
                'kebutuhan khusus' => 'kebutuhan_khusus',
                'sekolah asal' => 'sekolah_asal',
                'anak ke-berapa', 'anak keberapa' => 'anak_keberapa',
                'lintang' => 'lintang',
                'bujur' => 'bujur',
                'no kk' => 'no_kk',
                'berat badan' => 'berat_badan',
                'tinggi badan' => 'tinggi_badan',
                'lingkar kepala' => 'lingkar_kepala',
                'jml. saudara kandung', 'jml saudara kandung', 'jumlah saudara kandung' => 'jumlah_saudara_kandung',
                'jarak rumah ke sekolah (km)', 'jarak rumah ke sekolah' => 'jarak_rumah_km',
                'status', 'status siswa' => 'status_siswa',
                default => null,
            };

            if ($canonical !== null) {
                $map[$canonical] ??= $col;
            }
        }

        return $map;
    }

    protected function normalizeHeaderLabel($val): string
    {
        $text = preg_replace('/\s+/', ' ', str_replace(["\r\n", "\n", "\r"], ' ', (string) $val));
        return strtolower(trim((string) $text));
    }

    protected function normalizeSubHeader(string $label): string
    {
        return match ($label) {
            'nama' => 'nama',
            'tahun lahir' => 'tahun_lahir',
            'jenjang pendidikan' => 'jenjang_pendidikan',
            'pekerjaan' => 'pekerjaan',
            'penghasilan' => 'penghasilan',
            'nik' => 'nik',
            default => Str::slug($label, '_'),
        };
    }

    protected function normalizeMappedRow(array $row, array $colMap): array
    {
        $get = fn (string $key) => isset($colMap[$key]) ? $this->cellValue($row, $colMap[$key]) : null;

        $nama = $this->cleanText($get('nama'));
        $nipd = $this->cleanIdentifier($get('nipd'));
        $nisn = $this->cleanIdentifier($get('nisn'));
        $nik = $this->cleanIdentifier($get('nik'));
        $noKk = $this->cleanIdentifier($get('no_kk'));
        $jkRaw = strtoupper((string) $this->cleanText($get('jk')));
        $jk = in_array($jkRaw, ['L', 'P'], true) ? $jkRaw : null;

        $alamatJalan = $this->cleanText($get('alamat'));
        $rt = $this->cleanText($get('rt'));
        $rw = $this->cleanText($get('rw'));
        $dusun = $this->cleanText($get('dusun'));
        $kelurahan = $this->cleanKelurahan($this->cleanText($get('kelurahan')));
        $kecamatan = $this->cleanText($get('kecamatan'));
        $kodePos = $this->cleanIdentifier($get('kode_pos'));

        $fullAlamat = $this->composeFullAddress($alamatJalan, $rt, $rw, $kelurahan, $kecamatan, $kodePos);

        $namaDiKip = $this->cleanText($get('nama_di_kip'));
        if ($namaDiKip !== null && in_array($namaDiKip, ['0', '1', '-'], true)) {
            $namaDiKip = null;
        }

        $ortu = [];
        foreach (['ayah', 'ibu', 'wali'] as $hub) {
            $ortuNama = $this->cleanText($get("{$hub}_nama"));
            $pekerjaan = $this->cleanText($get("{$hub}_pekerjaan"));
            $penghasilan = $this->cleanPenghasilan($this->cleanText($get("{$hub}_penghasilan")));
            $tahunLahir = $this->cleanYear($get("{$hub}_tahun_lahir"));
            $ortuNik = $this->cleanIdentifier($get("{$hub}_nik"));
            $pendidikan = $this->cleanText($get("{$hub}_jenjang_pendidikan"));

            $isDeceased = ($pekerjaan !== null && stripos($pekerjaan, 'meninggal') !== false)
                || ($ortuNama !== null && stripos($ortuNama, '(ALM)') !== false);

            $ortu[$hub] = [
                'nama' => $ortuNama,
                'nik' => $ortuNik,
                'tahun_lahir' => $tahunLahir,
                'jenjang_pendidikan' => $pendidikan,
                'pekerjaan' => $pekerjaan,
                'penghasilan' => $penghasilan,
                'status_hidup' => $isDeceased ? 'meninggal' : 'hidup',
            ];
        }

        $lintang = $this->cleanFloat($get('lintang'));
        $bujur = $this->cleanFloat($get('bujur'));
        if ($lintang === 0.0 && $bujur === 0.0) {
            $lintang = null;
            $bujur = null;
        }

        $emailPribadi = $this->cleanText($get('email'));
        if ($emailPribadi !== null && !filter_var($emailPribadi, FILTER_VALIDATE_EMAIL)) {
            $emailPribadi = null;
        }

        return [
            'nama_lengkap' => $nama,
            'nipd' => $nipd,
            'nisn' => $nisn,
            'nik' => $nik,
            'no_kk' => $noKk,
            'jenis_kelamin' => $jk,
            'tempat_lahir' => $this->cleanText($get('tempat_lahir')),
            'tanggal_lahir' => $this->cleanDate($get('tanggal_lahir')),
            'agama' => $this->cleanText($get('agama')),
            'kelas' => $this->cleanText($get('rombel')),
            'alamat' => $fullAlamat,
            'no_telepon' => $this->cleanPhone($get('hp')) ?? $this->cleanPhone($get('telepon')),
            'telepon_rumah' => $this->cleanPhone($get('telepon')),
            'email_pribadi' => $emailPribadi,
            'status_siswa' => $this->cleanStatusSiswa($this->cleanText($get('status_siswa'))),
            'profil' => [
                'alamat_jalan' => $alamatJalan,
                'rt' => $rt,
                'rw' => $rw,
                'nama_dusun' => $dusun,
                'kelurahan' => $kelurahan,
                'kecamatan' => $kecamatan,
                'kode_pos' => $kodePos,
                'jenis_tinggal' => $this->cleanText($get('jenis_tinggal')),
                'alat_transportasi' => $this->cleanText($get('alat_transportasi')),
                'lintang' => $lintang,
                'bujur' => $bujur,
                'no_registrasi_akta_lahir' => $this->cleanText($get('no_registrasi_akta_lahir')),
                'skhun' => $this->cleanText($get('skhun')),
                'no_peserta_un' => $this->cleanText($get('no_peserta_un')),
                'no_seri_ijazah' => $this->cleanText($get('no_seri_ijazah')),
                'sekolah_asal' => $this->cleanText($get('sekolah_asal')),
                'kebutuhan_khusus' => $this->cleanText($get('kebutuhan_khusus')),
                'anak_keberapa' => $this->cleanInt($get('anak_keberapa'), 1, 30),
                'penerima_kps' => $this->cleanBool($get('penerima_kps')),
                'no_kps' => $this->cleanText($get('no_kps')),
                'penerima_kip' => $this->cleanBool($get('penerima_kip')),
                'nomor_kip' => $this->cleanText($get('nomor_kip')),
                'nama_di_kip' => $namaDiKip,
                'nomor_kks' => $this->cleanText($get('nomor_kks')),
                'layak_pip' => $this->cleanBool($get('layak_pip')),
                'alasan_layak_pip' => $this->cleanText($get('alasan_layak_pip')),
                'bank' => $this->cleanText($get('bank')),
                'nomor_rekening_bank' => $this->cleanIdentifier($get('nomor_rekening_bank')),
                'rekening_atas_nama' => $this->cleanText($get('rekening_atas_nama')),
            ],
            'ortu' => $ortu,
            'periodik' => [
                'berat_badan' => $this->cleanPositiveDecimal($get('berat_badan'), 200),
                'tinggi_badan' => $this->cleanPositiveDecimal($get('tinggi_badan'), 250),
                'lingkar_kepala' => $this->cleanPositiveDecimal($get('lingkar_kepala'), 100),
                'jumlah_saudara_kandung' => $this->cleanInt($get('jumlah_saudara_kandung'), 0, 25),
                'jarak_rumah_km' => $this->cleanPositiveDecimal($get('jarak_rumah_km'), 500),
            ],
        ];
    }

    protected function collectRowAnomalies(array $n, int $rowNumber): array
    {
        $issues = [];
        $nama = $n['nama_lengkap'] ?? 'Tanpa Nama';
        $add = function (string $msg) use (&$issues, $rowNumber, $nama) {
            $issues[] = [
                'row' => $rowNumber,
                'nama' => $nama,
                'type' => 'warning',
                'message' => $msg,
            ];
        };

        if (empty($n['nisn'])) {
            $add('NISN kosong');
        }
        if (empty($n['nipd'])) {
            $add('NIPD kosong');
        } elseif (preg_match('/^\d{4}\.\d{1,2}$/', $n['nipd']) === 1) {
            $add("NIPD '{$n['nipd']}' berpotensi terpotong angka 0 di belakang oleh Excel");
        }
        if (empty($n['nik'])) {
            $add('NIK siswa kosong / NULL');
        } elseif (preg_match('/^\d{16}$/', $n['nik']) !== 1) {
            $add("NIK siswa '{$n['nik']}' bukan 16 digit valid");
        }
        if (empty($n['jenis_kelamin'])) {
            $add('Jenis kelamin kosong');
        }
        if (empty($n['ortu']['ibu']['nama'])) {
            $add('Nama ibu kandung kosong');
        }
        $p = $n['profil'] ?? [];
        if (($p['lintang'] ?? null) !== null && ($p['bujur'] ?? null) !== null) {
            if ($p['lintang'] < -11.5 || $p['lintang'] > 6.5 || $p['bujur'] < 94.0 || $p['bujur'] > 142.0) {
                $add("Koordinat rumah ({$p['lintang']}, {$p['bujur']}) di luar wilayah Indonesia");
            }
        }

        return $issues;
    }

    protected function cellValue(array $row, ?int $index): ?string
    {
        if ($index === null || !array_key_exists($index, $row)) {
            return null;
        }
        return $row[$index] === null ? null : (string) $row[$index];
    }

    protected function cleanText(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }

        // Fix common Windows-1252 / UTF-8 mojibake from Dapodik CSV
        $val = str_replace(
            ["â€“", "â€”", "â€™", "Â ", "\u{00A0}"],
            ["–", "—", "'", " ", " "],
            $val
        );

        $val = trim(preg_replace('/\s+/', ' ', $val));
        if ($val === '' || strtoupper($val) === 'NULL' || $val === '-' || $val === '*') {
            return null;
        }

        return $val;
    }

    protected function cleanIdentifier(?string $val): ?string
    {
        $text = $this->cleanText($val);
        if ($text === null) {
            return null;
        }
        // Strip leading apostrophe if exported as Excel text literal
        $text = ltrim($text, "'");
        return $text === '' ? null : $text;
    }

    protected function cleanKelurahan(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }
        return trim(preg_replace('/^Desa\/Kel\.\s*/i', '', $val));
    }

    protected function cleanPenghasilan(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }

        $v = strtolower($val);
        return match (true) {
            str_contains($v, 'tidak berpenghasilan') => 'Tidak Berpenghasilan',
            str_contains($v, '< rp1.000.000') || str_contains($v, '500,000') => '< Rp 1.000.000',
            str_contains($v, '1.000.001') || str_contains($v, '1,000,000') => 'Rp 1.000.000 - Rp 3.000.000',
            str_contains($v, '2,000,000') || str_contains($v, '3.000.001') => 'Rp 2.000.000 - Rp 5.000.000',
            str_contains($v, '5,000,000') || str_contains($v, '20,000,000') => 'Rp 5.000.000 - Rp 20.000.000',
            default => $val,
        };
    }

    protected function cleanYear(?string $val): ?int
    {
        $text = $this->cleanText($val);
        if ($text === null || !is_numeric($text)) {
            return null;
        }
        $year = (int) $text;
        $maxYear = (int) date('Y');
        if ($year < 1920 || $year > $maxYear) {
            return null;
        }
        return $year;
    }

    protected function cleanDate(?string $val): ?string
    {
        $text = $this->cleanText($val);
        if ($text === null) {
            return null;
        }
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text) === 1) {
                return Carbon::parse(substr($text, 0, 10))->format('Y-m-d');
            }
            if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $text) === 1) {
                $sep = str_contains($text, '/') ? 'd/m/Y' : 'd-m-Y';
                return Carbon::createFromFormat($sep, $text)->format('Y-m-d');
            }
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function cleanPhone(?string $val): ?string
    {
        $text = $this->cleanText($val);
        if ($text === null || $text === '0') {
            return null;
        }
        return $text;
    }

    protected function cleanBool(?string $val): bool
    {
        $text = strtolower((string) $this->cleanText($val));
        return in_array($text, ['ya', 'y', 'yes', '1', 'true'], true);
    }

    protected function cleanFloat(?string $val): ?float
    {
        $text = $this->cleanText($val);
        if ($text === null) {
            return null;
        }
        $text = str_replace(',', '.', $text);
        return is_numeric($text) ? (float) $text : null;
    }

    protected function cleanPositiveDecimal(?string $val, float $max): ?float
    {
        $num = $this->cleanFloat($val);
        if ($num === null || $num <= 0 || $num > $max) {
            return null;
        }
        return $num;
    }

    protected function cleanInt(?string $val, int $min, int $max): ?int
    {
        $text = $this->cleanText($val);
        if ($text === null || !is_numeric($text)) {
            return null;
        }
        $num = (int) $text;
        return ($num >= $min && $num <= $max) ? $num : null;
    }

    protected function cleanStatusSiswa(?string $val): string
    {
        if ($val === null) {
            return 'aktif';
        }
        $v = strtolower($val);
        return match (true) {
            str_contains($v, 'lulus') || str_contains($v, 'alumni') => 'lulus',
            str_contains($v, 'mutasi') || str_contains($v, 'pindah') => 'mutasi_keluar',
            str_contains($v, 'keluar') || str_contains($v, 'drop') => 'drop_out',
            str_contains($v, 'undur') => 'mengundurkan_diri',
            str_contains($v, 'wafat') || str_contains($v, 'meninggal') => 'wafat',
            default => 'aktif',
        };
    }

    protected function composeFullAddress(?string $jalan, ?string $rt, ?string $rw, ?string $kelurahan, ?string $kecamatan, ?string $kodePos): ?string
    {
        $parts = array_filter([
            $jalan,
            ($rt || $rw) ? 'RT ' . ($rt ?? '-') . '/RW ' . ($rw ?? '-') : null,
            $kelurahan ? 'Kel. ' . $kelurahan : null,
            $kecamatan,
            $kodePos,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    protected function hasNonEmptyValues(array $arr): bool
    {
        foreach ($arr as $key => $val) {
            if ($key === 'status_hidup') {
                continue;
            }
            if ($val !== null && $val !== '' && $val !== false) {
                return true;
            }
        }
        return false;
    }
}
