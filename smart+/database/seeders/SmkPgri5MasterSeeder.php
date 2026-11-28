<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SmkPgri5MasterSeeder extends Seeder
{
    /**
     * Master seeder data real SMKS PGRI 5 Serpong (TA 2026/2027):
     * - Pengaturan Sekolah (shift 12:30-17:20, Sabtu aktif) & Tahun Ajaran 2026/2027
     * - 25 Guru + akun login
     * - 443 Siswa & 13 Rombel dari file Dapodik + akun login
     * - 51 Mata Pelajaran & Jadwal Pelajaran Senin-Sabtu (598 JP)
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PengaturanSekolahSeeder::class,
        ]);

        if (\App\Models\HariLibur::count() === 0) {
            $this->call(HariLiburSeeder::class);
        }

        $this->call([
            SmkPgri5SekolahSeeder::class,
            SmkPgri5GuruSeeder::class,
            SmkPgri5SiswaSeeder::class,
            SmkPgri5JadwalSeeder::class,
        ]);
    }
}
