<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Services\CacheService;
use Illuminate\Database\Seeder;

class RaporMataPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $akl = Jurusan::firstOrCreate(
            ['kode' => 'AKL'],
            ['singkatan' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'is_aktif' => true]
        );
        $mplb = Jurusan::firstOrCreate(
            ['kode' => 'MPLB'],
            ['singkatan' => 'MP', 'nama' => 'Manajemen Perkantoran dan Layanan Bisnis', 'is_aktif' => true]
        );
        $bd = Jurusan::firstOrCreate(
            ['kode' => 'BD'],
            ['singkatan' => 'BD', 'nama' => 'Bisnis Digital', 'is_aktif' => true]
        );

        // Nonaktifkan seluruh mapel lama/elemen dari tampilan E-Rapor tanpa menghapus jadwal mengajar
        MataPelajaran::query()->update(['tampil_di_rapor' => false]);

        $subjects = [
            // A. Pelajaran Umum
            ['kode' => 'A.01', 'nama' => 'Pendidikan Agama dan Budi Pekerti', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 10],
            ['kode' => 'A.02', 'nama' => 'Pendidikan Pancasila', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 20],
            ['kode' => 'A.03', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 30],
            ['kode' => 'A.04', 'nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 40],
            ['kode' => 'A.05', 'nama' => 'Sejarah', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 50],
            ['kode' => 'A.06', 'nama' => 'Seni', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['X'], 'urutan' => 60],
            ['kode' => '09-BTQ', 'nama' => 'Muatan Lokal BTQ', 'kelompok' => 'umum', 'jurusan_id' => null, 'tingkat' => ['XI', 'XII'], 'urutan' => 70],

            // B. Pelajaran Kejuruan (Umum & Dasar-dasar Kelas X)
            ['kode' => 'B.01', 'nama' => 'Matematika (Umum)', 'kelompok' => 'kejuruan', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 100],
            ['kode' => 'B.02', 'nama' => 'Bahasa Inggris', 'kelompok' => 'kejuruan', 'jurusan_id' => null, 'tingkat' => ['X', 'XI', 'XII'], 'urutan' => 110],
            ['kode' => 'B.03', 'nama' => 'Informatika', 'kelompok' => 'kejuruan', 'jurusan_id' => null, 'tingkat' => ['X'], 'urutan' => 120],
            ['kode' => 'B.04', 'nama' => 'Projek Ilmu Pengetahuan Alam dan Sosial', 'kelompok' => 'kejuruan', 'jurusan_id' => null, 'tingkat' => ['X'], 'urutan' => 130],
            ['kode' => 'RPR-X-AKL', 'nama' => 'Dasar-dasar Akuntansi dan Keuangan Lembaga', 'kelompok' => 'kejuruan', 'jurusan_id' => $akl->id, 'tingkat' => ['X'], 'urutan' => 140],
            ['kode' => 'RPR-X-MPLB', 'nama' => 'Dasar-dasar Manajemen Perkantoran dan Layanan Bisnis', 'kelompok' => 'kejuruan', 'jurusan_id' => $mplb->id, 'tingkat' => ['X'], 'urutan' => 140],
            ['kode' => 'RPR-X-BD', 'nama' => 'Dasar-dasar Pemasaran', 'kelompok' => 'kejuruan', 'jurusan_id' => $bd->id, 'tingkat' => ['X'], 'urutan' => 140],

            // C. Konsentrasi Keahlian Manajemen Perkantoran (XI & XII)
            ['kode' => 'MP-B2', 'nama' => 'Pengelolaan Administrasi Umum', 'kelompok' => 'konsentrasi', 'jurusan_id' => $mplb->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 200],
            ['kode' => 'MP-B3', 'nama' => 'Komunikasi di Tempat Kerja', 'kelompok' => 'konsentrasi', 'jurusan_id' => $mplb->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 210],
            ['kode' => 'MP-B4', 'nama' => 'Pengelolaan Kearsipan', 'kelompok' => 'konsentrasi', 'jurusan_id' => $mplb->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 220],
            ['kode' => 'MP-B5-TP', 'nama' => 'Teknologi Perkantoran', 'kelompok' => 'konsentrasi', 'jurusan_id' => $mplb->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 230],
            ['kode' => 'RPR-MP-PIL', 'nama' => 'Mata Pelajaran Pilihan (Teknik Perkantoran)', 'kelompok' => 'konsentrasi', 'jurusan_id' => $mplb->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 260],

            // C. Konsentrasi Keahlian Akuntansi (XI & XII)
            ['kode' => 'AKL-B1', 'nama' => 'Praktikum Akuntansi Perusahaan Jasa, Dagang', 'kelompok' => 'konsentrasi', 'jurusan_id' => $akl->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 200],
            ['kode' => 'AKL-B15', 'nama' => 'Praktikum Akuntansi Lembaga/Instansi Pemerintah', 'kelompok' => 'konsentrasi', 'jurusan_id' => $akl->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 210],
            ['kode' => 'AKL-B3-AK', 'nama' => 'Akuntansi Keuangan', 'kelompok' => 'konsentrasi', 'jurusan_id' => $akl->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 220],
            ['kode' => 'AKL-B3-KP', 'nama' => 'Komputer Akuntansi/Myob', 'kelompok' => 'konsentrasi', 'jurusan_id' => $akl->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 230],
            ['kode' => 'AKL-B4', 'nama' => 'Administrasi Pajak', 'kelompok' => 'konsentrasi', 'jurusan_id' => $akl->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 240],

            // C. Konsentrasi Keahlian Bisnis Digital (XI & XII)
            ['kode' => 'RPR-BD-EBAU', 'nama' => 'Ekonomi Bisnis dan Adm. Umum', 'kelompok' => 'konsentrasi', 'jurusan_id' => $bd->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 200],
            ['kode' => 'RPR-BD-MKT', 'nama' => 'Marketing', 'kelompok' => 'konsentrasi', 'jurusan_id' => $bd->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 210],
            ['kode' => 'RPR-BD-PB', 'nama' => 'Perencanaan Bisnis', 'kelompok' => 'konsentrasi', 'jurusan_id' => $bd->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 220],
            ['kode' => 'RPR-BD-KP', 'nama' => 'Komunikasi dengan Pelanggan', 'kelompok' => 'konsentrasi', 'jurusan_id' => $bd->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 230],
            ['kode' => 'RPR-BD-PIL', 'nama' => 'Mata Pelajaran Pilihan (Bisnis Digital)', 'kelompok' => 'konsentrasi', 'jurusan_id' => $bd->id, 'tingkat' => ['XI', 'XII'], 'urutan' => 260],

            // Shared Projek Kreatif dan Kewirausahaan across XI & XII Konsentrasi
            ['kode' => '06', 'nama' => 'Projek Kreatif dan Kewirausahaan', 'kelompok' => 'konsentrasi', 'jurusan_id' => null, 'tingkat' => ['XI', 'XII'], 'urutan' => 250],
        ];

        foreach ($subjects as $item) {
            MataPelajaran::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'kelompok' => $item['kelompok'],
                    'jurusan_id' => $item['jurusan_id'],
                    'tingkat' => $item['tingkat'],
                    'urutan' => $item['urutan'],
                    'tampil_di_rapor' => true,
                    'kkm' => 75.00,
                ]
            );
        }

        CacheService::flushDropdowns();
    }
}
