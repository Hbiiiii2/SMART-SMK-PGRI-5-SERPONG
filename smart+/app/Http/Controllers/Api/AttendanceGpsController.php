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
            'accuracy' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'altitude_accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'gps_timestamp' => 'nullable|numeric',
            'client_timestamp' => 'nullable|numeric',
            'samples' => 'nullable|array|max:10',
            'device_token' => 'nullable|string|max:64',
            'is_mock' => 'nullable|boolean',
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

        // 3b. Evaluasi Integritas GPS (Telemetri Mock GPS & Perangkat Bersama)
        $integrity = $this->evaluateGpsIntegrity($request, $user, $pengaturan, $today);
        if ($integrity['blocked']) {
            return response()->json([
                'success' => false,
                'message' => $integrity['message'],
            ], 400);
        }

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
            return $this->saveSiswaAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng, $integrity);
        }

        if ($user->isGuru()) {
            return $this->saveGuruAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng, $integrity);
        }

        return $this->saveStaffAttendance($user, $today, $now, $recordedLocation, $fileName, $pengaturan, $type, $userLat, $userLng, $integrity);
    }

    private function evaluateGpsIntegrity(Request $request, $user, PengaturanSekolah $pengaturan, string $today): array
    {
        $modeMock = $pengaturan->mode_anti_mock_gps ?? 'peringatan';
        $maksAkurasi = max(10, (int) ($pengaturan->maks_akurasi_gps_meter ?? 100));
        $mockReasons = [];
        $accuracy = $request->filled('accuracy') ? (float) $request->accuracy : null;

        if ($modeMock !== 'nonaktif') {
            if ($request->boolean('is_mock')) {
                $mockReasons[] = 'Terdeteksi flag Mock Location Provider';
            }

            if ($accuracy !== null) {
                if ($accuracy > $maksAkurasi) {
                    $mockReasons[] = 'Akurasi GPS rendah (±' . round($accuracy) . 'm > batas ' . $maksAkurasi . 'm)';
                } elseif ($accuracy <= 1.0) {
                    $mockReasons[] = 'Indikasi Mock GPS: nilai akurasi terlalu sempurna/tidak wajar (±' . $accuracy . 'm)';
                }
            }

            if (is_array($request->samples) && count($request->samples) >= 3) {
                $coords = [];
                foreach ($request->samples as $sample) {
                    if (is_array($sample) && isset($sample['lat'], $sample['lng'])) {
                        $coords[] = round((float) $sample['lat'], 7) . ',' . round((float) $sample['lng'], 7);
                    }
                }
                if (count($coords) >= 3 && count(array_unique($coords)) === 1) {
                    $mockReasons[] = 'Indikasi Mock GPS: koordinat multi-sampel statis tanpa micro-drift sensor';
                }
            }

            if ($request->filled('gps_timestamp') && $request->filled('client_timestamp')) {
                $diffMs = abs((float) $request->client_timestamp - (float) $request->gps_timestamp);
                if ($diffMs > 300000) {
                    $mockReasons[] = 'Indikasi Mock GPS: timestamp sensor GPS tidak sinkron';
                }
            }

            if (!empty($mockReasons) && $modeMock === 'ketat') {
                return [
                    'blocked' => true,
                    'message' => 'Absensi GPS ditolak karena terindikasi Mock GPS / sinyal tidak valid: ' . implode('; ', $mockReasons),
                ];
            }
        }

        $modeDevice = $pengaturan->mode_deteksi_perangkat_bersama ?? 'tandai';
        $deviceToken = $request->filled('device_token') ? trim((string) $request->device_token) : null;
        $deviceReasons = [];

        if ($modeDevice !== 'nonaktif' && !empty($deviceToken)) {
            $siswaCount = AbsensiSiswa::whereDate('tanggal', $today)
                ->where('device_token', $deviceToken)
                ->when($user->isSiswa() && $user->siswa, fn ($q) => $q->where('siswa_id', '!=', $user->siswa->id))
                ->distinct()
                ->count('siswa_id');

            $guruCount = AbsensiGuru::whereDate('tanggal', $today)
                ->where('device_token', $deviceToken)
                ->when($user->isGuru() && $user->guru, fn ($q) => $q->where('guru_id', '!=', $user->guru->id))
                ->distinct()
                ->count('guru_id');

            $isStaffLike = !$user->isSiswa() && !$user->isGuru();
            $staffCount = AbsensiStaff::whereDate('tanggal', $today)
                ->where('device_token', $deviceToken)
                ->when($isStaffLike && $user->staff_akademik, fn ($q) => $q->where('staff_akademik_id', '!=', $user->staff_akademik->id))
                ->distinct()
                ->count('staff_akademik_id');

            $otherAccountsCount = $siswaCount + $guruCount + $staffCount;
            $totalAccountsOnDevice = $otherAccountsCount + 1;
            $batas = max(2, (int) ($pengaturan->batas_akun_per_perangkat ?? 2));

            if ($totalAccountsOnDevice >= $batas) {
                $deviceReasons[] = "Perangkat bersama ({$totalAccountsOnDevice} akun absen dari HP yang sama hari ini)";

                if ($modeDevice === 'blokir') {
                    return [
                        'blocked' => true,
                        'message' => "Absensi ditolak: Perangkat ini sudah digunakan oleh {$otherAccountsCount} akun lain hari ini (batas kebijakan sekolah: maksimal " . ($batas - 1) . ' akun per HP).',
                    ];
                }
            }
        }

        $allReasons = array_values(array_unique(array_merge($mockReasons, $deviceReasons)));

        return [
            'blocked' => false,
            'gps_accuracy' => $accuracy !== null ? round($accuracy, 2) : null,
            'device_token' => $deviceToken ?: null,
            'is_mock_suspect' => !empty($allReasons),
            'mock_gps_reason' => !empty($allReasons) ? mb_substr(implode('; ', $allReasons), 0, 500) : null,
        ];
    }

    private function mergeIntegrityReasons(?string $existingReason, ?string $newReason): ?string
    {
        $parts = array_filter(array_map('trim', array_merge(
            $existingReason ? explode(';', $existingReason) : [],
            $newReason ? explode(';', $newReason) : []
        )));

        if (empty($parts)) {
            return null;
        }

        return mb_substr(implode('; ', array_values(array_unique($parts))), 0, 500);
    }

    private function saveSiswaAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng, array $integrity): JsonResponse
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
                        'gps_accuracy' => $integrity['gps_accuracy'],
                        'device_token' => $integrity['device_token'],
                        'is_mock_suspect' => $integrity['is_mock_suspect'],
                        'mock_gps_reason' => $integrity['mock_gps_reason'],
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
                    'gps_accuracy' => $integrity['gps_accuracy'],
                    'device_token' => $integrity['device_token'],
                    'is_mock_suspect' => $integrity['is_mock_suspect'],
                    'mock_gps_reason' => $integrity['mock_gps_reason'],
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
            Cache::forget('ks_dashboard_' . $today);

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
                'gps_accuracy' => $integrity['gps_accuracy'] ?? $rec->gps_accuracy,
                'device_token' => $integrity['device_token'] ?? $rec->device_token,
                'is_mock_suspect' => (bool) ($rec->is_mock_suspect || $integrity['is_mock_suspect']),
                'mock_gps_reason' => $this->mergeIntegrityReasons($rec->mock_gps_reason, $integrity['mock_gps_reason']),
            ]);
        }

        Cache::forget('live_stats_' . $today);
        Cache::forget('ks_dashboard_' . $today);

        return response()->json([
            'success' => true,
            'message' => 'Check-Out siswa berhasil dicatat.',
            'status' => $openRecords->first()->status,
        ]);
    }

    private function saveGuruAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng, array $integrity): JsonResponse
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
        $absensi->gps_accuracy = $integrity['gps_accuracy'] ?? $absensi->gps_accuracy;
        $absensi->device_token = $integrity['device_token'] ?? $absensi->device_token;
        $absensi->is_mock_suspect = (bool) ($absensi->is_mock_suspect || $integrity['is_mock_suspect']);
        $absensi->mock_gps_reason = $this->mergeIntegrityReasons($absensi->mock_gps_reason, $integrity['mock_gps_reason']);
        $absensi->save();

        Cache::forget('live_stats_' . $today);
        Cache::forget('ks_dashboard_' . $today);

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat.', 'status' => $absensi->status]);
    }

    private function saveStaffAttendance($user, string $today, Carbon $now, $lokasi, ?string $fotoPath, PengaturanSekolah $pengaturan, string $type, float $lat, float $lng, array $integrity): JsonResponse
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
        $absensi->gps_accuracy = $integrity['gps_accuracy'] ?? $absensi->gps_accuracy;
        $absensi->device_token = $integrity['device_token'] ?? $absensi->device_token;
        $absensi->is_mock_suspect = (bool) ($absensi->is_mock_suspect || $integrity['is_mock_suspect']);
        $absensi->mock_gps_reason = $this->mergeIntegrityReasons($absensi->mock_gps_reason, $integrity['mock_gps_reason']);
        $absensi->save();

        Cache::forget('live_stats_' . $today);
        Cache::forget('ks_dashboard_' . $today);

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
