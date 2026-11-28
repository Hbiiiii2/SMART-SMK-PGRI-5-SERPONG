<?php

namespace Database\Seeders;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\CacheService;
use App\Services\DapodikImportService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class SmkPgri5SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/daftar_peserta_didik_dapodik_26-27.xlsx');
        if (!file_exists($filePath)) {
            throw new RuntimeException("File data siswa Dapodik tidak ditemukan di: {$filePath}");
        }

        $ta = TahunAjaran::where('is_aktif', true)->first()
            ?? TahunAjaran::firstOrCreate(
                ['nama' => '2026/2027'],
                [
                    'tanggal_mulai' => '2026-07-01',
                    'tanggal_selesai' => '2027-06-30',
                    'is_aktif' => true,
                ]
            );

        $uploadedFile = new UploadedFile(
            $filePath,
            'daftar_peserta_didik_dapodik_26-27.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        /** @var DapodikImportService $importService */
        $importService = app(DapodikImportService::class);
        $log = $importService->import(
            file: $uploadedFile,
            tahunAjaranId: $ta->id,
            semester: 'Ganjil',
            createAccounts: true,
            defaultPassword: 'password',
            userId: null
        );

        if ($log->error_count > 0) {
            $firstErr = $log->anomalies[0]['message'] ?? 'Unknown import error';
            throw new RuntimeException("Import seeder siswa Dapodik mencatat {$log->error_count} error: {$firstErr}");
        }

        // Migrasi in-place akun siswa yang masih memakai format subdomain lama (@siswa.smkpgri5serpong.sch.id)
        Siswa::with('user')
            ->whereHas('user', fn ($q) => $q->where('email', 'like', '%@siswa.smkpgri5serpong.sch.id'))
            ->get()
            ->each(function (Siswa $siswa) use ($importService) {
                if (!$siswa->user) {
                    return;
                }

                $newEmail = $importService->generateUniqueStudentLoginEmail([
                    'nipd' => $siswa->nipd,
                    'nisn' => $siswa->nisn,
                    'nama_lengkap' => $siswa->nama_lengkap,
                ], $siswa->user->id);

                if ($siswa->user->email !== $newEmail) {
                    $siswa->user->update(['email' => $newEmail]);
                }
            });

        Rombel::where('tahun_ajaran_id', $ta->id)
            ->whereNull('kurikulum')
            ->update(['kurikulum' => 'Kurikulum Merdeka']);

        Rombel::where('tahun_ajaran_id', $ta->id)
            ->whereNull('kapasitas')
            ->update(['kapasitas' => 40]);

        CacheService::flushDropdowns();
    }
}
