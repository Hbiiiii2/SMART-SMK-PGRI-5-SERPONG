<?php

namespace Database\Seeders;

use App\Models\ExamCardSetting;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SmkPgri5SekolahSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed admin sekolah SMKS PGRI 5 Serpong (tidak menimpa password yang sudah ada)
        User::firstOrCreate(
            ['email' => 'admin@smkpgri5serpong.sch.id'],
            [
                'name' => 'Administrator SMKS PGRI 5 Serpong',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Sinkronkan pengaturan Kartu Peserta Ujian
        $cardSetting = ExamCardSetting::first() ?? new ExamCardSetting();
        $cardSetting->fill([
            'header_1' => 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP) PGRI',
            'header_2' => 'PROVINSI BANTEN - KOTA TANGERANG SELATAN',
            'header_3' => 'SMKS PGRI 5 SERPONG',
            'header_4' => 'Jl. Raya Puspiptek Serpong, Kota Tangerang Selatan, Banten',
            'judul_kartu' => 'KARTU PESERTA UJIAN',
            'tempat_tanggal' => 'Tangerang Selatan, Juli 2026',
            'jabatan_penandatangan' => 'Kepala Sekolah',
            'nama_penandatangan' => 'Encum Sumiati, S.Pd',
            'nip_penandatangan' => 'NIP. 202607010001',
        ]);
        $cardSetting->save();

        // 3. Muat data master kelas & mata pelajaran dari jadwal JSON
        $jsonPath = database_path('seeders/data/jadwal_smk_pgri_5_2026_2027.json');
        if (!file_exists($jsonPath)) {
            throw new RuntimeException("File jadwal JSON tidak ditemukan di: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($payload) || empty($payload['classes']) || empty($payload['subjects'])) {
            throw new RuntimeException('Format JSON jadwal SMKS PGRI 5 tidak valid.');
        }

        // 4. Upsert 13 Rombel/Kelas aktif
        foreach ($payload['classes'] as $namaKelas) {
            $namaKelas = trim((string) $namaKelas);
            if ($namaKelas === '') {
                continue;
            }

            Kelas::updateOrCreate(
                ['nama_kelas' => $namaKelas],
                [
                    'tingkat' => $this->inferTingkat($namaKelas),
                    'status' => 'aktif',
                ]
            );
        }

        // 5. Upsert 51 Mata Pelajaran sesuai kode jadwal resmi
        foreach ($payload['subjects'] as $subject) {
            $kode = trim((string) ($subject['kode'] ?? ''));
            $nama = trim((string) ($subject['nama'] ?? ''));
            if ($kode === '' || $nama === '') {
                continue;
            }

            MataPelajaran::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'deskripsi' => $nama,
                ]
            );
        }
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
