<?php

namespace App\Services;

use App\Models\DapodikSyncLog;
use App\Models\Guru;
use App\Models\PengaturanSekolah;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Http;

class DapodikApiService
{
    public function __construct(
        protected DapodikImportService $importService
    ) {}

    public function isConfigured(): bool
    {
        $settings = PengaturanSekolah::current();

        return !empty($settings->npsn)
            && !empty($settings->dapodik_url)
            && !empty($settings->dapodik_token);
    }

    public function testConnection(): array
    {
        $settings = PengaturanSekolah::current();
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Konfigurasi Dapodik belum lengkap (NPSN, URL WebService, dan Token wajib diisi di Pengaturan Sekolah).',
            ];
        }

        $baseUrl = rtrim((string) $settings->dapodik_url, '/');
        try {
            $response = Http::withToken((string) $settings->dapodik_token)
                ->acceptJson()
                ->timeout(8)
                ->get("{$baseUrl}/WebService/getSekolah", [
                    'npsn' => $settings->npsn,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => "Server Dapodik merespons HTTP {$response->status()}.",
                ];
            }

            $payload = $response->json();
            $rows = $payload['rows'] ?? $payload['data'] ?? $payload;
            $sekolah = is_array($rows) && isset($rows[0]) ? $rows[0] : (is_array($rows) ? $rows : []);

            return [
                'success' => true,
                'message' => 'Koneksi WebService Dapodik berhasil terhubung.',
                'sekolah' => $sekolah,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi WebService Dapodik: ' . $e->getMessage(),
            ];
        }
    }

    public function fetchRombonganBelajar(): array
    {
        return $this->fetchEndpoint('getRombonganBelajar');
    }

    public function fetchPesertaDidik(): array
    {
        return $this->fetchEndpoint('getPesertaDidik');
    }

    public function syncFromApi(
        ?int $tahunAjaranId = null,
        string $semester = 'Ganjil',
        bool $createAccounts = false,
        ?int $userId = null
    ): DapodikSyncLog {
        $startedAt = now();
        $settings = PengaturanSekolah::current();

        if (!$this->isConfigured()) {
            return DapodikSyncLog::create([
                'user_id' => $userId,
                'source' => 'api',
                'entity' => 'peserta_didik',
                'status' => 'failed',
                'error_count' => 1,
                'summary_notes' => 'Konfigurasi Dapodik WebService API belum diisi di Pengaturan Sekolah.',
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);
        }

        $taId = $tahunAjaranId
            ?? TahunAjaran::where('is_aktif', true)->value('id')
            ?? TahunAjaran::orderByDesc('id')->value('id');

        try {
            $rombelRows = $this->fetchRombonganBelajar();
            $this->syncRombels($rombelRows, (int) $taId, $semester);

            $pdRows = $this->fetchPesertaDidik();
            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;
            $anomalies = [];

            foreach ($pdRows as $idx => $pd) {
                $normalized = $this->mapApiPesertaDidik($pd);
                if (empty($normalized['nama_lengkap'])) {
                    $skipped++;
                    continue;
                }

                try {
                    $res = $this->importService->upsertNormalizedStudent(
                        normalized: $normalized,
                        tahunAjaranId: (int) $taId,
                        semester: $semester,
                        createAccounts: $createAccounts,
                        defaultPassword: null,
                        syncSource: 'api_dapodik',
                        rowNumber: $idx + 1
                    );

                    if ($res['action'] === 'inserted') {
                        $inserted++;
                    } elseif ($res['action'] === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    foreach ($res['anomalies'] as $a) {
                        $anomalies[] = $a;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $anomalies[] = [
                        'row' => $idx + 1,
                        'nama' => $normalized['nama_lengkap'],
                        'type' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $settings->update(['dapodik_last_sync_at' => now()]);
            CacheService::flushDropdowns();

            return DapodikSyncLog::create([
                'user_id' => $userId,
                'source' => 'api',
                'entity' => 'peserta_didik',
                'status' => $errors > 0 ? 'partial' : 'success',
                'total_rows' => count($pdRows),
                'inserted_count' => $inserted,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'warning_count' => count($anomalies),
                'error_count' => $errors,
                'summary_notes' => "Sinkronisasi WebService API Dapodik selesai: {$inserted} baru, {$updated} diperbarui, {$skipped} dilewati.",
                'anomalies' => array_slice($anomalies, 0, 200),
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return DapodikSyncLog::create([
                'user_id' => $userId,
                'source' => 'api',
                'entity' => 'peserta_didik',
                'status' => 'failed',
                'error_count' => 1,
                'summary_notes' => 'Sinkronisasi API Dapodik gagal: ' . $e->getMessage(),
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);
        }
    }

    protected function fetchEndpoint(string $endpoint): array
    {
        $settings = PengaturanSekolah::current();
        $baseUrl = rtrim((string) $settings->dapodik_url, '/');
        $query = ['npsn' => $settings->npsn];
        if (!empty($settings->dapodik_semester_id)) {
            $query['semester_id'] = $settings->dapodik_semester_id;
        }

        $response = Http::withToken((string) $settings->dapodik_token)
            ->acceptJson()
            ->timeout(20)
            ->get("{$baseUrl}/WebService/{$endpoint}", $query);

        $response->throw();
        $json = $response->json();

        return $json['rows'] ?? $json['data'] ?? (is_array($json) ? $json : []);
    }

    protected function syncRombels(array $rows, int $tahunAjaranId, string $semester): void
    {
        foreach ($rows as $row) {
            $nama = trim((string) ($row['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $uuid = $row['rombongan_belajar_id'] ?? null;
            $meta = Rombel::inferMetaFromName($nama);

            $waliId = null;
            $ptkName = trim((string) ($row['ptk_id_str'] ?? $row['nama_wali_kelas'] ?? ''));
            if ($ptkName !== '') {
                $waliId = Guru::whereRaw('LOWER(nama_lengkap) = ?', [strtolower($ptkName)])->value('id');
            }

            $rombel = $uuid
                ? Rombel::where('dapodik_rombongan_belajar_id', $uuid)->first()
                : null;

            if (!$rombel) {
                $rombel = Rombel::where('tahun_ajaran_id', $tahunAjaranId)
                    ->where('semester', $semester)
                    ->where('nama', $nama)
                    ->first();
            }

            $payload = [
                'dapodik_rombongan_belajar_id' => $uuid ?? $rombel?->dapodik_rombongan_belajar_id,
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
                'nama' => $nama,
                'tingkat' => $row['tingkat_pendidikan_id_str'] ?? $meta['tingkat'],
                'kompetensi_keahlian' => $row['jurusan_id_str'] ?? $meta['kompetensi_keahlian'],
                'kurikulum' => $row['kurikulum_id_str'] ?? $rombel?->kurikulum,
                'wali_kelas_guru_id' => $waliId ?? $rombel?->wali_kelas_guru_id,
                'is_aktif' => true,
            ];

            if ($rombel) {
                $rombel->update($payload);
            } else {
                $rombel = Rombel::create($payload);
            }

            $rombel->syncHomeroom();
        }
    }

    protected function mapApiPesertaDidik(array $pd): array
    {
        $jk = strtoupper(trim((string) ($pd['jenis_kelamin'] ?? '')));
        $email = trim((string) ($pd['email'] ?? ''));

        return [
            'dapodik_peserta_didik_id' => $pd['peserta_didik_id'] ?? null,
            'dapodik_registrasi_id' => $pd['registrasi_id'] ?? null,
            'dapodik_rombongan_belajar_id' => $pd['rombongan_belajar_id'] ?? null,
            'dapodik_anggota_rombel_id' => $pd['anggota_rombel_id'] ?? null,
            'nama_lengkap' => trim((string) ($pd['nama'] ?? '')),
            'nipd' => $this->nullableString($pd['nipd'] ?? null),
            'nisn' => $this->nullableString($pd['nisn'] ?? null),
            'nik' => $this->nullableString($pd['nik'] ?? null),
            'jenis_kelamin' => in_array($jk, ['L', 'P'], true) ? $jk : null,
            'tempat_lahir' => $this->nullableString($pd['tempat_lahir'] ?? null),
            'tanggal_lahir' => $this->nullableString($pd['tanggal_lahir'] ?? null),
            'agama_id' => isset($pd['agama_id']) ? (int) $pd['agama_id'] : null,
            'agama' => $this->nullableString($pd['agama_id_str'] ?? null),
            'kelas' => $this->nullableString($pd['nama_rombel'] ?? null),
            'no_telepon' => $this->nullableString($pd['nomor_telepon_seluler'] ?? null),
            'telepon_rumah' => $this->nullableString($pd['nomor_telepon_rumah'] ?? null),
            'email_pribadi' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'tanggal_masuk' => $this->nullableString($pd['tanggal_masuk_sekolah'] ?? null),
            'status_siswa' => 'aktif',
            'profil' => [
                'sekolah_asal' => $this->nullableString($pd['sekolah_asal'] ?? null),
                'anak_keberapa' => isset($pd['anak_keberapa']) && is_numeric($pd['anak_keberapa']) ? (int) $pd['anak_keberapa'] : null,
                'raw_dapodik_payload' => $pd,
            ],
            'ortu' => [
                'ayah' => [
                    'nama' => $this->nullableString($pd['nama_ayah'] ?? null),
                    'pekerjaan' => $this->nullableString($pd['pekerjaan_ayah_id_str'] ?? null),
                ],
                'ibu' => [
                    'nama' => $this->nullableString($pd['nama_ibu'] ?? null),
                    'pekerjaan' => $this->nullableString($pd['pekerjaan_ibu_id_str'] ?? null),
                ],
                'wali' => [
                    'nama' => $this->nullableString($pd['nama_wali'] ?? null),
                    'pekerjaan' => $this->nullableString($pd['pekerjaan_wali_id_str'] ?? null),
                ],
            ],
            'periodik' => [
                'berat_badan' => isset($pd['berat_badan']) && is_numeric($pd['berat_badan']) && (float) $pd['berat_badan'] > 0 ? (float) $pd['berat_badan'] : null,
                'tinggi_badan' => isset($pd['tinggi_badan']) && is_numeric($pd['tinggi_badan']) && (float) $pd['tinggi_badan'] > 0 ? (float) $pd['tinggi_badan'] : null,
            ],
        ];
    }

    protected function nullableString($val): ?string
    {
        $s = trim((string) ($val ?? ''));
        return ($s === '' || strtoupper($s) === 'NULL') ? null : $s;
    }
}
