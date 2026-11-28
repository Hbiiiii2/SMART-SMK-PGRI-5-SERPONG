<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SmkPgri5GuruSeeder extends Seeder
{
    /**
     * Daftar 25 Guru SMKS PGRI 5 Serpong Tahun Ajaran 2026/2027
     * sesuai kode jadwal mengajar resmi (6 Juli 2026).
     */
    public const TEACHERS = [
        ['code' => 'ES', 'nip' => '202607010001', 'nama_lengkap' => 'Encum Sumiati, S.Pd', 'jenis_kelamin' => 'P', 'email' => 'encum@smkpgri5serpong.sch.id'],
        ['code' => 'WS', 'nip' => '202607010002', 'nama_lengkap' => 'Wawan Suhairi, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'wawan@smkpgri5serpong.sch.id'],
        ['code' => 'YS', 'nip' => '202607010003', 'nama_lengkap' => 'Yayat Supriyatna, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'yayat@smkpgri5serpong.sch.id'],
        ['code' => 'AS', 'nip' => '202607010004', 'nama_lengkap' => 'Agus Setyo Siswanto, SE', 'jenis_kelamin' => 'L', 'email' => 'agus@smkpgri5serpong.sch.id'],
        ['code' => 'NS', 'nip' => '202607010005', 'nama_lengkap' => 'Nining Sariningsih, S.Pd, MM', 'jenis_kelamin' => 'P', 'email' => 'nining@smkpgri5serpong.sch.id'],
        ['code' => 'DH', 'nip' => '202607010006', 'nama_lengkap' => 'Didiek Hery Priyantono, SE', 'jenis_kelamin' => 'L', 'email' => 'didiek@smkpgri5serpong.sch.id'],
        ['code' => 'YL', 'nip' => '202607010007', 'nama_lengkap' => 'Yulianti, S.Pd', 'jenis_kelamin' => 'P', 'email' => 'yulianti@smkpgri5serpong.sch.id'],
        ['code' => 'IW', 'nip' => '202607010008', 'nama_lengkap' => 'Moh Inggar Wijaya, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'inggar@smkpgri5serpong.sch.id'],
        ['code' => 'EY', 'nip' => '202607010009', 'nama_lengkap' => 'Erna Yulianti, S.Pd', 'jenis_kelamin' => 'P', 'email' => 'erna@smkpgri5serpong.sch.id'],
        ['code' => 'SH', 'nip' => '202607010010', 'nama_lengkap' => 'Drs. Syarif Hidayat', 'jenis_kelamin' => 'L', 'email' => 'syarif@smkpgri5serpong.sch.id'],
        ['code' => 'MK', 'nip' => '202607010011', 'nama_lengkap' => 'Mila Karmila, S.E', 'jenis_kelamin' => 'P', 'email' => 'mila@smkpgri5serpong.sch.id'],
        ['code' => 'PO', 'nip' => '202607010012', 'nama_lengkap' => 'Prio Oktavianto, M.Pd', 'jenis_kelamin' => 'L', 'email' => 'prio@smkpgri5serpong.sch.id'],
        ['code' => 'AR', 'nip' => '202607010013', 'nama_lengkap' => 'Arief Rahman Hakim, SE', 'jenis_kelamin' => 'L', 'email' => 'arief@smkpgri5serpong.sch.id'],
        ['code' => 'HM', 'nip' => '202607010014', 'nama_lengkap' => 'Heris Mujiati, S.Pd', 'jenis_kelamin' => 'P', 'email' => 'heris@smkpgri5serpong.sch.id'],
        ['code' => 'MA', 'nip' => '202607010015', 'nama_lengkap' => 'Mimin Aminah, M.Pd', 'jenis_kelamin' => 'P', 'email' => 'mimin@smkpgri5serpong.sch.id'],
        ['code' => 'NB', 'nip' => '202607010016', 'nama_lengkap' => 'Nurbaeti, SE', 'jenis_kelamin' => 'P', 'email' => 'nurbaeti@smkpgri5serpong.sch.id'],
        ['code' => 'RH', 'nip' => '202607010017', 'nama_lengkap' => 'Rendy Hatdyan Mansur, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'rendy@smkpgri5serpong.sch.id'],
        ['code' => 'AF', 'nip' => '202607010018', 'nama_lengkap' => 'M. Afifuddin Nur, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'afifuddin@smkpgri5serpong.sch.id'],
        ['code' => 'FY', 'nip' => '202607010019', 'nama_lengkap' => 'Fitri Yuliana, S.M', 'jenis_kelamin' => 'P', 'email' => 'fitri@smkpgri5serpong.sch.id'],
        ['code' => 'NP', 'nip' => '202607010020', 'nama_lengkap' => 'Nira Puspita, S.M', 'jenis_kelamin' => 'P', 'email' => 'nira@smkpgri5serpong.sch.id'],
        ['code' => 'NF', 'nip' => '202607010021', 'nama_lengkap' => 'Anisa Fitri, S.M', 'jenis_kelamin' => 'P', 'email' => 'anisa@smkpgri5serpong.sch.id'],
        ['code' => 'SJ', 'nip' => '202607010022', 'nama_lengkap' => 'Silma Julfaika Sibli, S.Pd', 'jenis_kelamin' => 'P', 'email' => 'silma@smkpgri5serpong.sch.id'],
        ['code' => 'RD', 'nip' => '202607010023', 'nama_lengkap' => 'Ridwan Iriawan, S.Pd', 'jenis_kelamin' => 'L', 'email' => 'ridwan@smkpgri5serpong.sch.id'],
        ['code' => 'DY', 'nip' => '202607010024', 'nama_lengkap' => 'Dicky Yahya Aria Putera, S.Ds', 'jenis_kelamin' => 'L', 'email' => 'dicky@smkpgri5serpong.sch.id'],
        ['code' => 'DS', 'nip' => '202607010025', 'nama_lengkap' => 'Danang Setiadi', 'jenis_kelamin' => 'L', 'email' => 'danang@smkpgri5serpong.sch.id'],
    ];

    public function run(): void
    {
        foreach (self::TEACHERS as $item) {
            $existingGuru = Guru::with('user')->where('nip', $item['nip'])->first();

            if ($existingGuru && $existingGuru->user) {
                $user = $existingGuru->user;
                $user->update([
                    'name' => $item['nama_lengkap'],
                    'email' => $item['email'],
                    'role' => 'guru',
                    'is_active' => true,
                ]);
            } else {
                $user = User::firstOrCreate(
                    ['email' => $item['email']],
                    [
                        'name' => $item['nama_lengkap'],
                        'password' => Hash::make('password'),
                        'role' => 'guru',
                        'is_active' => true,
                    ]
                );

                if ($user->name !== $item['nama_lengkap'] || !$user->is_active || $user->role !== 'guru') {
                    $user->update([
                        'name' => $item['nama_lengkap'],
                        'role' => 'guru',
                        'is_active' => true,
                    ]);
                }
            }

            Guru::updateOrCreate(
                ['nip' => $item['nip']],
                [
                    'user_id' => $user->id,
                    'nama_lengkap' => $item['nama_lengkap'],
                    'jenis_kelamin' => $item['jenis_kelamin'],
                ]
            );
        }

        User::where('email', 'kepalasekolah@smart.sch.id')
            ->update(['name' => 'Encum Sumiati, S.Pd']);
    }
}
