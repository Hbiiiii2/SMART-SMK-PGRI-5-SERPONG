<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengaturanSekolahRequest;
use App\Models\PengaturanSekolah;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Cache;

class PengaturanSekolahController extends Controller
{
    use LogsActivity;

    public function edit()
    {
        $pengaturan = PengaturanSekolah::firstOrCreate([], [
            'nama_sekolah' => 'Nama Sekolah',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => true,
            'izinkan_absen_tanpa_jadwal' => true,
            'absensi_hari_sabtu' => false,
            'absensi_hari_minggu' => false,
        ]);

        return view('pages.admin.pengaturan-sekolah.edit', [
            'title' => 'Pengaturan Sekolah',
            'pengaturan' => $pengaturan,
            'isSuperAdmin' => auth()->user()->role === 'super_admin',
        ]);
    }

    public function update(PengaturanSekolahRequest $request)
    {
        $pengaturan = PengaturanSekolah::firstOrFail();
        $data = $request->validated();

        foreach ([
            'validasi_radius_gps',
            'wajib_foto_gps',
            'izinkan_absen_tanpa_jadwal',
            'absensi_hari_sabtu',
            'absensi_hari_minggu',
        ] as $boolField) {
            if ($request->has($boolField)) {
                $data[$boolField] = $request->boolean($boolField);
            }
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logo', 'public');
        }

        $pengaturan->update($data);

        Cache::forget('pengaturan_sekolah');
        Cache::forget('admin_dashboard');
        CacheService::flushDropdowns();

        $this->logActivity('update_pengaturan', 'Mengubah pengaturan sekolah', $request);

        return back()->with('success', 'Pengaturan sekolah berhasil diperbarui.');
    }
}
