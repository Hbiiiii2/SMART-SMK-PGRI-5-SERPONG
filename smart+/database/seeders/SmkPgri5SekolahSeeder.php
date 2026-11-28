<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Lokasi;
use App\Models\PengaturanSekolah;
use App\Models\RaporSetting;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SmkPgri5SekolahSeeder extends Seeder
{
    public const JURUSANS = [
        [
            'kode' => 'AKL',
            'singkatan' => 'AK',
            'nama' => 'Akuntansi dan Keuangan Lembaga',
            'deskripsi' => 'Kompetensi keahlian akuntansi, perpajakan, audit, dan komputer akuntansi (MYOB/Accurate).',
        ],
        [
            'kode' => 'MPLB',
            'singkatan' => 'MP',
            'nama' => 'Manajemen Perkantoran dan Layanan Bisnis',
            'deskripsi' => 'Kompetensi keahlian administrasi perkantoran modern, kearsipan, dan layanan pelanggan.',
        ],
        [
            'kode' => 'BD',
            'singkatan' => 'BD',
            'nama' => 'Bisnis Digital',
            'deskripsi' => 'Kompetensi keahlian pemasaran digital, SEO/SEM, content marketing, dan e-commerce.',
        ],
    ];

    public const CLASSES = [
        'X AK',
        'X BD',
        'X MP 1',
        'X MP 2',
        'XI AK 1',
        'XI AK 2',
        'XI BD',
        'XI MP 1',
        'XI MP 2',
        'XII AK',
        'XII BD',
        'XII MP 1',
        'XII MP 2',
    ];

    public function run(): void
    {
        // 1. Seed Master Jurusan / Kejuruan SMKS PGRI 5 Serpong
        foreach (self::JURUSANS as $j) {
            Jurusan::updateOrCreate(
                ['kode' => $j['kode']],
                [
                    'singkatan' => $j['singkatan'],
                    'nama' => $j['nama'],
                    'deskripsi' => $j['deskripsi'],
                    'is_aktif' => true,
                ]
            );
        }

        // Backfill rombel.jurusan_id bila rombel sudah ada sebelumnya
        Rombel::whereNull('jurusan_id')->get()->each(function (Rombel $rombel) {
            $jurusan = Jurusan::resolveFromRombelName($rombel->nama);
            if ($jurusan) {
                $rombel->update([
                    'jurusan_id' => $jurusan->id,
                    'kompetensi_keahlian' => $jurusan->nama,
                ]);
            }
        });

        // 2. Update Pengaturan Sekolah sesuai jam belajar SMKS PGRI 5 Serpong (shift siang & Sabtu aktif)
        $pengaturan = PengaturanSekolah::current();
        $pengaturan->update([
            'nama_sekolah' => 'SMKS PGRI 5 Serpong',
            'alamat_sekolah' => 'Jl. Raya Puspiptek Serpong, Kota Tangerang Selatan, Banten',
            'no_telepon_sekolah' => $pengaturan->no_telepon_sekolah ?: '021-75871234',
            'email_sekolah' => $pengaturan->email_sekolah ?: 'info@smkpgri5serpong.sch.id',
            'jam_masuk' => '12:30',
            'jam_pulang' => '17:20',
            'absensi_hari_sabtu' => true,
            'absensi_hari_minggu' => false,
            'dapodik_semester_id' => '20261',
        ]);

        // 3. Pastikan Tahun Ajaran 2026/2027 aktif (nonaktifkan tahun ajaran lain)
        $ta2026 = TahunAjaran::updateOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'is_aktif' => true,
            ]
        );
        TahunAjaran::where('id', '!=', $ta2026->id)->update(['is_aktif' => false]);

        // 4. Pastikan Lokasi default & ruang kelas untuk ke-13 rombel tersedia
        Lokasi::firstOrCreate(
            ['nama' => 'Gedung A'],
            [
                'deskripsi' => 'Gedung utama SMKS PGRI 5 Serpong',
                'latitude' => -6.3145,
                'longitude' => 106.6695,
                'radius' => 100,
            ]
        );

        foreach (self::CLASSES as $kelas) {
            Lokasi::firstOrCreate(
                ['nama' => "Ruang {$kelas}"],
                [
                    'deskripsi' => "Ruang kelas {$kelas} SMKS PGRI 5 Serpong",
                    'latitude' => -6.3145,
                    'longitude' => 106.6695,
                    'radius' => 100,
                ]
            );
        }

        // 5. Sinkronkan RaporSetting bila tabel tersedia
        if (Schema::hasTable('rapor_settings')) {
            RaporSetting::instance()->update([
                'nama_kurikulum' => 'Kurikulum Merdeka',
                'kota_pembagian' => 'Kota Tangerang Selatan',
                'nama_kepala_sekolah' => 'Encum Sumiati, S.Pd',
                'header_yayasan' => 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP) PGRI',
                'header_sekolah' => 'SMKS PGRI 5 SERPONG',
            ]);
        }
    }
}
