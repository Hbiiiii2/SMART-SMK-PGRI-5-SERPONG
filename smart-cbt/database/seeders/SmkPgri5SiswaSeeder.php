<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SmkPgri5SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/siswa_smk_pgri_5_2026_2027.json');
        if (!file_exists($jsonPath)) {
            throw new RuntimeException("File JSON siswa tidak ditemukan di: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($payload) || empty($payload['students'])) {
            throw new RuntimeException('Format JSON siswa SMKS PGRI 5 tidak valid.');
        }

        $kelasMap = Kelas::pluck('id', 'nama_kelas')->all();
        $defaultPasswordHash = Hash::make('password');

        DB::transaction(function () use ($payload, &$kelasMap, $defaultPasswordHash): void {
            foreach ($payload['students'] as $student) {
                $nama = trim((string) ($student['nama'] ?? ''));
                $nis = trim((string) ($student['nis'] ?? ''));
                $nisn = !empty($student['nisn']) ? trim((string) $student['nisn']) : null;
                $kelasNama = trim((string) ($student['kelas'] ?? ''));
                $email = strtolower(trim((string) ($student['email'] ?? '')));
                $tahunAjaran = trim((string) ($student['tahun_ajaran'] ?? '2026/2027'));

                if ($nama === '' || $nis === '' || $email === '') {
                    continue;
                }

                $kelasId = null;
                if ($kelasNama !== '') {
                    if (!isset($kelasMap[$kelasNama])) {
                        $kelas = Kelas::firstOrCreate(
                            ['nama_kelas' => $kelasNama],
                            [
                                'tingkat' => $this->inferTingkat($kelasNama),
                                'status' => 'aktif',
                            ]
                        );
                        $kelasMap[$kelasNama] = $kelas->id;
                    }
                    $kelasId = $kelasMap[$kelasNama];
                }

                $existingSiswa = Siswa::with('user')
                    ->where('nis', $nis)
                    ->when($nisn !== null, fn ($q) => $q->orWhere('nisn', $nisn))
                    ->first();

                if ($existingSiswa && $existingSiswa->user) {
                    $user = $existingSiswa->user;
                    $updates = [
                        'name' => $nama,
                        'role' => 'siswa',
                    ];
                    if ($user->email !== $email && !User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                        $updates['email'] = $email;
                    }
                    $user->update($updates);
                } else {
                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $nama,
                            'password' => $defaultPasswordHash,
                            'role' => 'siswa',
                        ]
                    );

                    if ($user->name !== $nama || $user->role !== 'siswa') {
                        $user->update([
                            'name' => $nama,
                            'role' => 'siswa',
                        ]);
                    }
                }

                Siswa::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'nama' => $nama,
                        'kelas' => $kelasNama !== '' ? $kelasNama : null,
                        'kelas_id' => $kelasId,
                        'program_tahasus' => false,
                        'tahun_ajaran' => $tahunAjaran !== '' ? $tahunAjaran : '2026/2027',
                        'status_aktif' => true,
                    ]
                );
            }
        });
    }

    protected function inferTingkat(string $namaKelas): string
    {
        if (str_starts_with($namaKelas, 'XII ')) {
            return 'XII';
        }
        if (str_starts_with($namaKelas, 'XI ')) {
            return 'XI';
        }
        if (str_starts_with($namaKelas, 'X ')) {
            return 'X';
        }

        return 'X';
    }
}
