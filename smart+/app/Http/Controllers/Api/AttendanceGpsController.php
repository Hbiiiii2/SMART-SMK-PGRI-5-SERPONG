<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\AbsensiStaff;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\PengaturanSekolah;
use App\Models\StaffAkademik;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AttendanceGpsController extends Controller
{
    public function checkIn(Request $request): JsonResponse
    {
        return $this->processAttendance($request, 'in');
    }

    public function checkOut(Request $request): JsonResponse
    {
        return $this->processAttendance($request, 'out');
    }

    private function processAttendance(Request $request, string $type): JsonResponse
    {
        $user = auth()->user();
        if (!$user || (
            !$user->isSiswa()
            && !$user->isGuru()
            && !$user->isStaffAkademik()
            && !$user->isKepalaSekolah()
            && !$user->isDirektur()
            && !$user->isAdmin()
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses untuk melakukan absensi GPS.',
            ], 403);
        }

        $pengaturan = PengaturanSekolah::first();
        if (!$pengaturan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan sekolah belum dikonfigurasi.',
            ], 500);
        }

        if (!$pengaturan->allowsGpsForRole($user->role)) {
            return response()->json([
                'success' => false,
                'message' => 'Metode absensi untuk role Anda saat ini diset hanya RFID. Silakan ubah ke Zonasi atau Hybrid di Pengaturan Sekolah.',
            ], 403);
        }

        $wajibFoto = (bool) ($pengaturan->wajib_foto_gps ?? true);

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'foto' => $wajibFoto ? 'required|string' : 'nullable|string',
        ]);

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        // 1. Cek Hari Akhir Pekan sesuai pengaturan sekolah
        if ($pengaturan->isWeekendHoliday($now)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi pada hari akhir pekan belum diaktifkan di Pengaturan Sekolah.',
            ], 400);
        }

        // 2. Cek Hari Libur
        if (HariLibur::isLibur($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini adalah hari libur, absensi tidak dapat dilakukan.',
            ], 400);
        }

        // 3. Verifikasi Lokasi & Zonasi
        $allowedLocations = $user->getEffectiveAllowedLocations($pengaturan);
        $validasiRadius = (bool) ($pengaturan->validasi_radius_gps ?? true);

        if ($validasiRadius && $allowedLocations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada titik lokasi absensi yang diizinkan atau dikonfigurasi. Silakan tambahkan lokasi di menu Lokasi atau atur izin lokasi di Pengaturan Sekolah.',
            ], 403);
        }

        $validLocation = null;
        $nearestLocation = null;
        $nearestDistance = null;
        $userLat = (float) $request->latitude;
        $userLng = (float) $request->longitude;
        $defaultRadius = (float) ($pengaturan->radius_absensi_meter ?? 100);

        foreach ($allowedLocations as $lokasi) {
            $distance = $this->calculateDistance($userLat, $userLng, (float) $lokasi->latitude, (float) $lokasi->longitude);
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestLocation = $lokasi;
            }
            $radius = (float) ($lokasi->radius ?: $defaultRadius);
            if ($distance <= $radius && $validLocation === null) {
                $validLocation = $lokasi;
            }
        }

        if ($validasiRadius && !$validLocation) {
            $jarakInfo = $nearestDistance !== null ? ' (jarak terdekat: ' . round($nearestDistance) . ' meter)' : '';
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar jangkauan (radius) lokasi absensi yang diizinkan' . $jarakInfo . '.',
            ], 400);
        }

        $recordedLocation = $validLocation ?? $nearestLocation;

        // 4. Proses Foto (wajib atau opsional sesuai pengaturan sekolah)
        $fileName = null;
        if ($request->filled('foto')) {
            $imageData = $request->foto;
            $imageParts = explode(';base64,', $imageData);
            if (count($imageParts) !== 2) {
                return response()->json(['success' => false, 'message' => 'Format foto tidak valid.'], 400);
            }
            $imageTypeAux = explode('image/', $imageParts[0]);
            $rawType = strtolower($imageTypeAux[1] ?? 'jpeg');
            $ext = in_array($rawType, ['jpeg', 'jpg', 'png', 'webp'], true)
                ? ($rawType === 'jpeg' ? 'jpg' : $rawType)
                : 'jpg';
            $imageBase64 = base64_decode($imageParts[1], true);
            if ($imageBase64 === false) {
                return response()->json(['success' => false, 'message' => 'Data foto tidak valid.'], 400);
            }
            $fileName = 'absensi/' . $user->id . '_' . $now->timestamp . '.' . $ext;
            Storage::disk('public')->put($fileName, $imageBase64);
        } elseif ($wajibFoto) {
            return response()->json(['success' => false, 'message' => 'Foto live wajib diambil sebelum melakukan absensi.'], 400);
        }

        // 5. Simpan Data Absensi berdasarkan role
        if ($user->isSiswa()) {
            return $this->saveSiswaAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng);
        }

        if ($user->isGuru()) {
            return $this->saveGuruAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng);
        }

        return $this->saveStaffAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng);
    }

    private function saveSiswaAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng): JsonResponse
    {
        $siswa = $user->siswa;
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data profil siswa tidak ditemukan.'], 404);
        }

        $jamSekarang = $now->format('H:i:s');

        if ($type === 'in') {
            $alreadyCheckedIn = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $today)
                ->whereNotNull('jam_masuk')
                ->exists();

            if ($alreadyCheckedIn) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-In hari ini.'], 400);
            }

            $status = $this->determineStatus($now, $pengaturan);

            $dayName = match ($now->dayOfWeek) {
                0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
            };

            $jadwalsToday = Jadwal::where('kelas', $siswa->kelas)
                ->where('hari', $dayName)
                ->when($siswa->tahun_ajaran_id, fn ($q) => $q->where('tahun_ajaran_id', $siswa->tahun_ajaran_id))
                ->orderBy('jam_mulai')
                ->get();

            if ($jadwalsToday->isNotEmpty()) {
                foreach ($jadwalsToday as $idx => $jadwal) {
                    $existing = AbsensiSiswa::where('siswa_id', $siswa->id)
                        ->where('jadwal_id', $jadwal->id)
                        ->whereDate('tanggal', $today)
                        ->first();

                    $payload = [
                        'lokasi_id' => $lokasi?->id,
                        'status' => $status,
                        'jam_masuk' => $jamSekarang,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'foto_lokasi' => $fotoPath,
                        'keterangan' => $idx === 0 ? 'Absensi GPS Lokasi' : 'Otomatis mengikuti absen GPS pertama',
                    ];

                    if ($existing) {
                        $existing->update($payload);
                    } else {
                        AbsensiSiswa::create(array_merge($payload, [
                            'siswa_id' => $siswa->id,
                            'jadwal_id' => $jadwal->id,
                            'tanggal' => $today,
                        ]));
                    }
                }
            } else {
                if (!($pengaturan->izinkan_absen_tanpa_jadwal ?? true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada jadwal pelajaran untuk kelas ' . $siswa->kelas . ' hari ini.',
                    ], 400);
                }

                $daily = AbsensiSiswa::where('siswa_id', $siswa->id)
                    ->whereNull('jadwal_id')
                    ->whereDate('tanggal', $today)
                    ->first();

                $payload = [
                    'lokasi_id' => $lokasi?->id,
                    'status' => $status,
                    'jam_masuk' => $jamSekarang,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'foto_lokasi' => $fotoPath,
                    'keterangan' => 'Absensi GPS Harian (Tanpa Jadwal)',
                ];

                if ($daily) {
                    $daily->update($payload);
                } else {
                    AbsensiSiswa::create(array_merge($payload, [
                        'siswa_id' => $siswa->id,
                        'jadwal_id' => null,
                        'tanggal' => $today,
                    ]));
                }
            }

            Cache::forget('live_stats_' . $today);

            return response()->json([
                'success' => true,
                'message' => "Check-In siswa berhasil ({$status}).",
                'status' => $status,
            ]);
        }

        $openRecords = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->get();

        if ($openRecords->isEmpty()) {
            $alreadyCheckedOut = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $today)
                ->whereNotNull('jam_keluar')
                ->exists();

            return response()->json([
                'success' => false,
                'message' => $alreadyCheckedOut
                    ? 'Anda sudah melakukan Check-Out hari ini.'
                    : 'Anda belum melakukan Check-In. Silakan Check-In terlebih dahulu.',
            ], 400);
        }

        foreach ($openRecords as $rec) {
            $rec->update([
                'jam_keluar' => $jamSekarang,
                'latitude' => $lat,
                'longitude' => $lng,
                'foto_lokasi' => $fotoPath ?? $rec->foto_lokasi,
            ]);
        }

        Cache::forget('live_stats_' . $today);

        return response()->json([
            'success' => true,
            'message' => 'Check-Out siswa berhasil dicatat.',
            'status' => $openRecords->first()->status,
        ]);
    }

    private function saveGuruAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng): JsonResponse
    {
        $guru = $user->guru;
        if (!$guru) {
            return response()->json(['success' => false, 'message' => 'Data profil guru tidak ditemukan.'], 404);
        }

        $absensi = AbsensiGuru::firstOrNew([
            'guru_id' => $guru->id,
            'tanggal' => $today,
        ]);

        if ($type === 'in') {
            if ($absensi->exists && $absensi->jam_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-In hari ini.'], 400);
            }
            $absensi->jam_masuk = $now->format('H:i:s');
            $absensi->status = $this->determineStatus($now, $pengaturan);
            $absensi->lokasi_id = $lokasi?->id;
        } else {
            if (!$absensi->exists || !$absensi->jam_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda belum melakukan Check-In. Silakan Check-In terlebih dahulu.'], 400);
            }
            if ($absensi->jam_keluar) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-Out hari ini.'], 400);
            }
            $absensi->jam_keluar = $now->format('H:i:s');
        }

        $absensi->latitude = $lat;
        $absensi->longitude = $lng;
        if ($fotoPath) {
            $absensi->foto_lokasi = $fotoPath;
        }
        $absensi->save();

        Cache::forget('live_stats_' . $today);

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat.', 'status' => $absensi->status]);
    }

    private function saveStaffAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng): JsonResponse
    {
        $staff = $user->staff_akademik;
        if (!$staff) {
            $staff = StaffAkademik::create([
                'pengguna_id' => $user->id,
                'nama_lengkap' => $user->name,
                'jenis_kelamin' => 'L',
                'status_aktif' => true,
            ]);
        }

        $absensi = AbsensiStaff::firstOrNew([
            'staff_akademik_id' => $staff->id,
            'tanggal' => $today,
        ]);

        if ($type === 'in') {
            if ($absensi->exists && $absensi->waktu_check_in) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-In hari ini.'], 400);
            }
            $absensi->waktu_check_in = $now;
            $absensi->status = $this->determineStatus($now, $pengaturan);
            $absensi->lokasi_id = $lokasi?->id;
        } else {
            if (!$absensi->exists || !$absensi->waktu_check_in) {
                return response()->json(['success' => false, 'message' => 'Anda belum melakukan Check-In. Silakan Check-In terlebih dahulu.'], 400);
            }
            if ($absensi->waktu_check_out) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-Out hari ini.'], 400);
            }
            $absensi->waktu_check_out = $now;
        }

        $absensi->latitude = $lat;
        $absensi->longitude = $lng;
        if ($fotoPath) {
            $absensi->foto_lokasi = $fotoPath;
        }
        $absensi->save();

        Cache::forget('live_stats_' . $today);

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat.', 'status' => $absensi->status]);
    }

    private function determineStatus(Carbon $now, PengaturanSekolah $pengaturan): string
    {
        $jamMasuk = Carbon::parse($now->toDateString() . ' ' . $pengaturan->jam_masuk, 'Asia/Jakarta');
        $batasToleransi = $jamMasuk->copy()->addMinutes($pengaturan->toleransi_terlambat_menit);

        if ($now->lessThanOrEqualTo($batasToleransi)) {
            return 'Hadir';
        }

        return 'Terlambat';
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
