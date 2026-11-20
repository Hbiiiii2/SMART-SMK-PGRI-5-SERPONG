<?php

namespace App\Http\Controllers\StaffAkademik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AbsensiStaff;
use App\Models\PengajuanCuti;
use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $staff = $user->staff_akademik;
        
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $pengaturan = PengaturanSekolah::first();
        $isLibur = ($pengaturan ? $pengaturan->isWeekendHoliday($now) : ($now->isSaturday() || $now->isSunday()))
            || HariLibur::isLibur($today);

        $absensiHariIni = null;
        $historyAbsensi = collect();
        $historyCuti = collect();

        $absensiStatus = null;
        if ($staff) {
            $absensiHariIni = AbsensiStaff::where('staff_akademik_id', $staff->id)
                ->where('tanggal', $today)
                ->first();
                
            if ($absensiHariIni) {
                $absensiStatus = [
                    'sudah_check_in' => $absensiHariIni->waktu_check_in !== null,
                    'sudah_check_out' => $absensiHariIni->waktu_check_out !== null,
                    'jam_masuk' => $absensiHariIni->waktu_check_in ? Carbon::parse($absensiHariIni->waktu_check_in)->format('H:i:s') : null,
                    'jam_keluar' => $absensiHariIni->waktu_check_out ? Carbon::parse($absensiHariIni->waktu_check_out)->format('H:i:s') : null,
                    'status' => $absensiHariIni->status
                ];
            }
                
            $historyAbsensi = AbsensiStaff::where('staff_akademik_id', $staff->id)
                ->latest('tanggal')
                ->limit(30)
                ->get();
                
            $historyCuti = PengajuanCuti::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();
        }

        $allowedLocations = collect();
        if ($user) {
            $allowedLocations = $user->getEffectiveAllowedLocations($pengaturan);
        }

        return view('pages.staff.dashboard', compact(
            'staff', 'absensiHariIni', 'historyAbsensi', 'historyCuti', 
            'isLibur', 'pengaturan', 'allowedLocations', 'absensiStatus'
        ));
    }
}
