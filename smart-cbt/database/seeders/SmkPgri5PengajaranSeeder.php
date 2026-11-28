<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengajaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmkPgri5PengajaranSeeder extends Seeder
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

        $guruMap = Guru::pluck('id', 'kode_guru')->all();
        $mapelMap = MataPelajaran::pluck('id', 'kode')->all();
        $kelasMap = Kelas::pluck('id', 'nama_kelas')->all();

        $uniquePairs = [];
        foreach ($payload['schedule_blocks'] as $block) {
            $teacherCode = trim((string) ($block['teacher_code'] ?? ''));
            $subjectKode = trim((string) ($block['subject_kode'] ?? ''));
            $kelasNama = trim((string) ($block['kelas'] ?? ''));

            $key = "{$teacherCode}|{$subjectKode}|{$kelasNama}";
            if (!isset($uniquePairs[$key])) {
                $uniquePairs[$key] = [
                    'teacher_code' => $teacherCode,
                    'subject_kode' => $subjectKode,
                    'kelas' => $kelasNama,
                ];
            }
        }

        DB::transaction(function () use ($uniquePairs, $guruMap, $mapelMap, $kelasMap): void {
            foreach ($uniquePairs as $pair) {
                $guruId = $guruMap[$pair['teacher_code']] ?? null;
                $mapelId = $mapelMap[$pair['subject_kode']] ?? null;
                $kelasId = $kelasMap[$pair['kelas']] ?? null;

                if (!$guruId || !$mapelId || !$kelasId) {
                    throw new RuntimeException(
                        "Mapping pengajaran tidak lengkap untuk guru={$pair['teacher_code']}, mapel={$pair['subject_kode']}, kelas={$pair['kelas']}."
                    );
                }

                Pengajaran::updateOrCreate(
                    [
                        'guru_id' => $guruId,
                        'mata_pelajaran_id' => $mapelId,
                        'kelas_id' => $kelasId,
                    ],
                    [
                        'status' => 'aktif',
                    ]
                );
            }
        });
    }
}
