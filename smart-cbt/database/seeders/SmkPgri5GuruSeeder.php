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
     * identik dengan data master di SMART+ (email, NIP, nama, dan kode guru).
     */
    public const TEACHERS = [
        ['code' => 'ES', 'nip' => '202607010001', 'nama_lengkap' => 'Encum Sumiati, S.Pd', 'email' => 'encum@smkpgri5serpong.sch.id'],
        ['code' => 'WS', 'nip' => '202607010002', 'nama_lengkap' => 'Wawan Suhairi, S.Pd', 'email' => 'wawan@smkpgri5serpong.sch.id'],
        ['code' => 'YS', 'nip' => '202607010003', 'nama_lengkap' => 'Yayat Supriyatna, S.Pd', 'email' => 'yayat@smkpgri5serpong.sch.id'],
        ['code' => 'AS', 'nip' => '202607010004', 'nama_lengkap' => 'Agus Setyo Siswanto, SE', 'email' => 'agus@smkpgri5serpong.sch.id'],
        ['code' => 'NS', 'nip' => '202607010005', 'nama_lengkap' => 'Nining Sariningsih, S.Pd, MM', 'email' => 'nining@smkpgri5serpong.sch.id'],
        ['code' => 'DH', 'nip' => '202607010006', 'nama_lengkap' => 'Didiek Hery Priyantono, SE', 'email' => 'didiek@smkpgri5serpong.sch.id'],
        ['code' => 'YL', 'nip' => '202607010007', 'nama_lengkap' => 'Yulianti, S.Pd', 'email' => 'yulianti@smkpgri5serpong.sch.id'],
        ['code' => 'IW', 'nip' => '202607010008', 'nama_lengkap' => 'Moh Inggar Wijaya, S.Pd', 'email' => 'inggar@smkpgri5serpong.sch.id'],
        ['code' => 'EY', 'nip' => '202607010009', 'nama_lengkap' => 'Erna Yulianti, S.Pd', 'email' => 'erna@smkpgri5serpong.sch.id'],
        ['code' => 'SH', 'nip' => '202607010010', 'nama_lengkap' => 'Drs. Syarif Hidayat', 'email' => 'syarif@smkpgri5serpong.sch.id'],
        ['code' => 'MK', 'nip' => '202607010011', 'nama_lengkap' => 'Mila Karmila, S.E', 'email' => 'mila@smkpgri5serpong.sch.id'],
        ['code' => 'PO', 'nip' => '202607010012', 'nama_lengkap' => 'Prio Oktavianto, M.Pd', 'email' => 'prio@smkpgri5serpong.sch.id'],
        ['code' => 'AR', 'nip' => '202607010013', 'nama_lengkap' => 'Arief Rahman Hakim, SE', 'email' => 'arief@smkpgri5serpong.sch.id'],
        ['code' => 'HM', 'nip' => '202607010014', 'nama_lengkap' => 'Heris Mujiati, S.Pd', 'email' => 'heris@smkpgri5serpong.sch.id'],
        ['code' => 'MA', 'nip' => '202607010015', 'nama_lengkap' => 'Mimin Aminah, M.Pd', 'email' => 'mimin@smkpgri5serpong.sch.id'],
        ['code' => 'NB', 'nip' => '202607010016', 'nama_lengkap' => 'Nurbaeti, SE', 'email' => 'nurbaeti@smkpgri5serpong.sch.id'],
        ['code' => 'RH', 'nip' => '202607010017', 'nama_lengkap' => 'Rendy Hatdyan Mansur, S.Pd', 'email' => 'rendy@smkpgri5serpong.sch.id'],
        ['code' => 'AF', 'nip' => '202607010018', 'nama_lengkap' => 'M. Afifuddin Nur, S.Pd', 'email' => 'afifuddin@smkpgri5serpong.sch.id'],
        ['code' => 'FY', 'nip' => '202607010019', 'nama_lengkap' => 'Fitri Yuliana, S.M', 'email' => 'fitri@smkpgri5serpong.sch.id'],
        ['code' => 'NP', 'nip' => '202607010020', 'nama_lengkap' => 'Nira Puspita, S.M', 'email' => 'nira@smkpgri5serpong.sch.id'],
        ['code' => 'NF', 'nip' => '202607010021', 'nama_lengkap' => 'Anisa Fitri, S.M', 'email' => 'anisa@smkpgri5serpong.sch.id'],
        ['code' => 'SJ', 'nip' => '202607010022', 'nama_lengkap' => 'Silma Julfaika Sibli, S.Pd', 'email' => 'silma@smkpgri5serpong.sch.id'],
        ['code' => 'RD', 'nip' => '202607010023', 'nama_lengkap' => 'Ridwan Iriawan, S.Pd', 'email' => 'ridwan@smkpgri5serpong.sch.id'],
        ['code' => 'DY', 'nip' => '202607010024', 'nama_lengkap' => 'Dicky Yahya Aria Putera, S.Ds', 'email' => 'dicky@smkpgri5serpong.sch.id'],
        ['code' => 'DS', 'nip' => '202607010025', 'nama_lengkap' => 'Danang Setiadi', 'email' => 'danang@smkpgri5serpong.sch.id'],
    ];

    public function run(): void
    {
        $defaultPasswordHash = Hash::make('password');

        foreach (self::TEACHERS as $item) {
            $existingGuru = Guru::with('user')
                ->where('nip', $item['nip'])
                ->orWhere('kode_guru', $item['code'])
                ->first();

            if ($existingGuru && $existingGuru->user) {
                $user = $existingGuru->user;
                $user->update([
                    'name' => $item['nama_lengkap'],
                    'email' => $item['email'],
                    'role' => 'guru',
                ]);
            } else {
                $user = User::firstOrCreate(
                    ['email' => $item['email']],
                    [
                        'name' => $item['nama_lengkap'],
                        'password' => $defaultPasswordHash,
                        'role' => 'guru',
                    ]
                );

                if ($user->name !== $item['nama_lengkap'] || $user->role !== 'guru') {
                    $user->update([
                        'name' => $item['nama_lengkap'],
                        'role' => 'guru',
                    ]);
                }
            }

            Guru::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $item['nip'],
                    'nama' => $item['nama_lengkap'],
                    'kode_guru' => $item['code'],
                ]
            );
        }
    }
}
