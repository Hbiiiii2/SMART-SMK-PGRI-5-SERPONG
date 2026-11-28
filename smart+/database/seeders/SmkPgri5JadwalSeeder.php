<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Services\CacheService;
use Illuminate\Database\Seeder;
use RuntimeException;

class SmkPgri5JadwalSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/jadwal_smk_pgri_5_2026_2027.json');
        if (!file_exists($jsonPath)) {
            throw new RuntimeException("File jadwal JSON tidak ditemukan di: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($payload) || empty($payload['schedule_blocks'])) {
            throw new RuntimeException('Format JSON jadwal SMKS PGRI 5 tidak valid.');
        }

        $ta = TahunAjaran::where('nama', $payload['tahun_ajaran'] ?? '2026/2027')->first()
            ?? TahunAjaran::where('is_aktif', true)->firstOrFail();

        // Pastikan jurusan master & link rombel tersedia
        $jurusansByKode = Jurusan::pluck('id', 'kode');
        Rombel::where('tahun_ajaran_id', $ta->id)
            ->whereNull('jurusan_id')
            ->get()
            ->each(function (Rombel $rombel) {
                $jurusan = Jurusan::resolveFromRombelName($rombel->nama);
                if ($jurusan) {
                    $rombel->update([
                        'jurusan_id' => $jurusan->id,
                        'kompetensi_keahlian' => $jurusan->nama,
                    ]);
                }
            });

        // Hitung tingkat yang mengambil masing-masing kode mapel langsung dari jadwal blok
        $tingkatBySubject = [];
        foreach ($payload['schedule_blocks'] as $block) {
            $meta = Rombel::inferMetaFromName($block['kelas']);
            if (!empty($meta['tingkat'])) {
                $tingkatBySubject[$block['subject_kode']][$meta['tingkat']] = true;
            }
        }

        // 1. Upsert seluruh Mata Pelajaran SMKS PGRI 5 Serpong lengkap dengan kelompok, jurusan_id, tingkat, dan urutan
        $subjectMap = [];
        $orderCounters = ['umum' => 10, 'kejuruan' => 100, 'pilihan' => 200, 'mulok' => 300];
        foreach ($payload['subjects'] as $subject) {
            $kode = $subject['kode'];
            [$kelompok, $jurusanKode] = $this->classifySubject($kode);
            $jurusanId = $jurusanKode ? ($jurusansByKode[$jurusanKode] ?? null) : null;

            $levels = array_keys($tingkatBySubject[$kode] ?? []);
            usort($levels, fn ($a, $b) => (['X' => 1, 'XI' => 2, 'XII' => 3][$a] ?? 9) <=> (['X' => 1, 'XI' => 2, 'XII' => 3][$b] ?? 9));
            $tingkatField = (count($levels) > 0 && count($levels) < 3) ? $levels : null;

            $urutan = $orderCounters[$kelompok];
            $orderCounters[$kelompok] += 10;

            $mp = MataPelajaran::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $subject['nama'],
                    'kelompok' => $kelompok,
                    'jurusan_id' => $jurusanId,
                    'tingkat' => $tingkatField,
                    'urutan' => $urutan,
                    'kkm' => 75.00,
                    'deskripsi' => $subject['nama'],
                ]
            );
            $subjectMap[$kode] = $mp->id;
        }

        // 2. Bangun lookup Guru (kode inisial -> guru_id)
        $nipByCode = [];
        foreach (SmkPgri5GuruSeeder::TEACHERS as $t) {
            $nipByCode[$t['code']] = $t['nip'];
        }
        $guruByNip = Guru::whereIn('nip', array_values($nipByCode))->pluck('id', 'nip');
        $guruMap = [];
        foreach ($nipByCode as $code => $nip) {
            if (isset($guruByNip[$nip])) {
                $guruMap[$code] = $guruByNip[$nip];
            }
        }

        // 3. Bangun lookup Lokasi (Ruang {kelas} -> fallback Gedung A -> first Lokasi)
        $lokasis = Lokasi::pluck('id', 'nama');
        $fallbackLokasiId = $lokasis['Gedung A'] ?? Lokasi::query()->value('id');
        if (!$fallbackLokasiId) {
            $fallbackLokasiId = Lokasi::create([
                'nama' => 'Gedung A',
                'deskripsi' => 'Gedung utama SMKS PGRI 5 Serpong',
                'latitude' => -6.3145,
                'longitude' => 106.6695,
                'radius' => 100,
            ])->id;
        }

        // 4. Upsert seluruh blok Jadwal Pelajaran (Senin-Sabtu, semua 13 kelas)
        foreach ($payload['schedule_blocks'] as $block) {
            $guruId = $guruMap[$block['teacher_code']] ?? null;
            $mapelId = $subjectMap[$block['subject_kode']] ?? null;

            if (!$guruId || !$mapelId) {
                throw new RuntimeException(
                    "Mapping guru ({$block['teacher_code']}) atau mapel ({$block['subject_kode']}) tidak ditemukan untuk {$block['kelas']} {$block['hari']}."
                );
            }

            $lokasiId = $lokasis["Ruang {$block['kelas']}"] ?? $fallbackLokasiId;

            Jadwal::updateOrCreate(
                [
                    'tahun_ajaran_id' => $ta->id,
                    'kelas' => $block['kelas'],
                    'hari' => $block['hari'],
                    'jam_mulai' => $block['jam_mulai'],
                ],
                [
                    'guru_id' => $guruId,
                    'mata_pelajaran_id' => $mapelId,
                    'jam_selesai' => $block['jam_selesai'],
                    'lokasi_id' => $lokasiId,
                ]
            );
        }

        CacheService::flushDropdowns();
    }

    /**
     * Map SMK PGRI 5 subject code to [kelompok, jurusanKode|null].
     */
    protected function classifySubject(string $kode): array
    {
        if (str_starts_with($kode, 'AKL-')) {
            return ['kejuruan', 'AKL'];
        }
        if (str_starts_with($kode, 'MP-')) {
            return ['kejuruan', 'MPLB'];
        }
        if (str_starts_with($kode, 'BD-')) {
            return ['kejuruan', 'BD'];
        }
        if (str_starts_with($kode, '08')) {
            return ['pilihan', null];
        }
        if (str_starts_with($kode, '09')) {
            return ['mulok', null];
        }
        if (str_starts_with($kode, 'B.') || $kode === '06') {
            return ['kejuruan', null];
        }

        return ['umum', null];
    }
}
