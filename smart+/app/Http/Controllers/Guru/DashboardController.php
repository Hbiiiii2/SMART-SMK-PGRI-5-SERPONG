<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Materi;
use App\Models\PengaturanSekolah;
use App\Models\Tugas;
use App\Services\AttendanceService;
use App\Services\GuruLmsCardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $guru = auth()->user()->guru;
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $jadwals = Jadwal::where('guru_id', $guru->id)
            ->where('hari', $dayName)
            ->with('mataPelajaran', 'lokasi')
            ->get();

        $rekapJadwal = [];
        foreach ($jadwals as $j) {
            $absensi = AbsensiSiswa::where('jadwal_id', $j->id)
                ->where('tanggal', $today)
                ->get();
            $rekapJadwal[] = [
                'jadwal' => $j,
                'totalSiswa' => $absensi->count(),
                'hadirCount' => $absensi->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'alfaCount' => $absensi->where('status', 'Alfa')->count(),
                'izinCount' => $absensi->where('status', 'Izin')->count(),
                'sakitCount' => $absensi->where('status', 'Sakit')->count(),
            ];
        }

        $absensiStatus = app(AttendanceService::class)->getGuruAbsensiStatus($guru->id, $today);
        $settings = PengaturanSekolah::current();

        $user = auth()->user();
        $historyCuti = \App\Models\PengajuanCuti::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();
        $allowedLocations = $user->getEffectiveAllowedLocations($settings);
        $now = Carbon::now('Asia/Jakarta');
        $isLibur = $settings->isWeekendHoliday($now) || \App\Models\HariLibur::isLibur($today);
        $lmsClassCards = app(GuruLmsCardService::class)->buildClassCards($guru->id, $dayName);

        return view('pages.guru.dashboard', [
            'title' => 'Dashboard Guru',
            'guru' => $guru,
            'todayName' => $dayName,
            'jadwals' => $jadwals,
            'rekapJadwal' => $rekapJadwal,
            'lmsClassCards' => $lmsClassCards,
            'absensiStatus' => $absensiStatus,
            'metode' => $settings->metode_absensi_guru ?? 'RFID',
            'settings' => $settings,
            'pengaturan' => $settings,
            'today' => $today,
            'historyCuti' => $historyCuti,
            'allowedLocations' => $allowedLocations,
            'isLibur' => $isLibur,
        ]);
    }

    /**
     * Classroom LMS management view for a specific (kelas, mata_pelajaran_id) taught by the teacher.
     */
    public function showKelasLms(Request $request)
    {
        $request->validate([
            'kelas' => 'required|string',
            'mata_pelajaran_id' => 'required|integer|exists:mata_pelajaran,id',
        ]);

        $guru = auth()->user()->guru;
        $kelas = trim((string) $request->query('kelas'));
        $mapelId = (int) $request->query('mata_pelajaran_id');

        $hasJadwal = Jadwal::where('guru_id', $guru->id)
            ->where('kelas', $kelas)
            ->where('mata_pelajaran_id', $mapelId)
            ->exists();

        if (!$hasJadwal) {
            abort(403, 'Anda tidak terdaftar mengajar mata pelajaran ini pada kelas tersebut.');
        }

        $cards = app(GuruLmsCardService::class)->buildClassCards($guru->id);
        $classCard = $cards->first(fn ($c) => $c['kelas'] === $kelas && $c['mapel_id'] === $mapelId);

        if (!$classCard) {
            abort(404);
        }

        $materi = Materi::where('guru_id', $guru->id)
            ->where('kelas', $kelas)
            ->where('mata_pelajaran_id', $mapelId)
            ->latest()
            ->get();

        $tugas = Tugas::where('guru_id', $guru->id)
            ->where('kelas', $kelas)
            ->where('mata_pelajaran_id', $mapelId)
            ->withCount([
                'pengumpulanTugas',
                'pengumpulanTugas as belum_dinilai_count' => function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('nilai')->orWhere('status', '!=', 'dinilai');
                    });
                },
            ])
            ->latest()
            ->get();

        $activeTab = in_array($request->query('tab'), ['materi', 'tugas'], true)
            ? $request->query('tab')
            : 'materi';

        return view('pages.guru.kelas-lms', [
            'title' => "Kelas {$kelas} — {$classCard['mapel_nama']}",
            'guru' => $guru,
            'classCard' => $classCard,
            'materi' => $materi,
            'tugas' => $tugas,
            'activeTab' => $activeTab,
        ]);
    }
}
