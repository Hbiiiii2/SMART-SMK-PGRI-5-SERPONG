<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SmkPgri5MasterSeeder extends Seeder
{
    /**
     * Master seeder data real SMKS PGRI 5 Serpong (TA 2026/2027) untuk SMART-CBT:
     * - Admin CBT (admincbt@sch.id) & Admin SMKS PGRI 5 (admin@smkpgri5serpong.sch.id)
     * - Pengaturan Kartu Peserta Ujian SMKS PGRI 5 Serpong
     * - 13 Rombel/Kelas aktif (X, XI, XII: AK, BD, MP)
     * - 51 Mata Pelajaran sesuai struktur jadwal resmi
     * - 25 Guru + akun login (identik dengan SMART+)
     * - 443 Siswa + akun login (identik dengan SMART+ dari Dapodik TA 2026/2027)
     * - 192 Relasi Pengajaran (Guru - Mapel - Kelas) dari jadwal resmi
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SmkPgri5SekolahSeeder::class,
            SmkPgri5GuruSeeder::class,
            SmkPgri5SiswaSeeder::class,
            SmkPgri5PengajaranSeeder::class,
        ]);
    }
}
