<?php

namespace App\Console\Commands;

use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha';
    protected $description = 'Auto-mark Alpha/Alfa untuk guru, siswa, & staff yang belum absen setelah batas waktu';

    public function handle(AttendanceService $attendanceService): int
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        $settings = PengaturanSekolah::first();
        if (!$settings) {
            $this->error('Pengaturan sekolah tidak ditemukan.');
            return Command::FAILURE;
        }

        if ($settings->isWeekendHoliday($now)) {
            $this->info('Hari ini akhir pekan dan absensi akhir pekan tidak diaktifkan. Tidak ada auto-mark Alpha.');
            return Command::SUCCESS;
        }

        if (HariLibur::isLibur($today)) {
            $this->info('Hari ini hari libur nasional. Tidak ada auto-mark Alpha.');
            return Command::SUCCESS;
        }

        $guruCutoff = $settings->getEffectiveAlphaCutoffForRole('guru', $now);
        $siswaCutoff = $settings->getEffectiveAlphaCutoffForRole('siswa', $now);
        $staffCutoff = $settings->getEffectiveAlphaCutoffForRole('staff_akademik', $now);

        $this->info("Batas Alpha — Guru: {$guruCutoff->format('H:i')}, Siswa: {$siswaCutoff->format('H:i')}, Staff: {$staffCutoff->format('H:i')}");
        $this->info("Waktu sekarang: {$now->format('H:i:s')}");

        $result = $attendanceService->autoMarkAlphaIfPastCutoff($now);

        $this->info("Selesai! Guru Alpha: {$result['guru']}, Siswa Alfa: {$result['siswa']}, Staff Alpha: {$result['staff']}");

        return Command::SUCCESS;
    }
}
