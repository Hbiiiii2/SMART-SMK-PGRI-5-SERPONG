@php
    $gpsSettings = $pengaturan ?? $settings ?? \App\Models\PengaturanSekolah::first();
    $wajibFoto = (bool) ($gpsSettings->wajib_foto_gps ?? true);
    $validasiRadius = (bool) ($gpsSettings->validasi_radius_gps ?? true);
    $defaultRadius = (float) ($gpsSettings->radius_absensi_meter ?? 100);
    $gpsAllowedLocations = $allowedLocations ?? (auth()->user()?->getEffectiveAllowedLocations($gpsSettings) ?? collect());
    $gpsIsLibur = $isLibur ?? false;
    $sudahCheckin = (bool) ($absensiStatus['sudah_check_in'] ?? false);
    $sudahCheckout = (bool) ($absensiStatus['sudah_check_out'] ?? false);
@endphp

<div class="relative mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Kehadiran (GPS &amp; Kamera)</h3>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium {{ $validasiRadius ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                {{ $validasiRadius ? 'Validasi Radius Sekolah' : 'Mode Bebas Radius' }}
            </span>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium {{ $wajibFoto ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $wajibFoto ? 'Foto Wajib' : 'Foto Opsional' }}
            </span>
        </div>
    </div>

    @if($sudahCheckin)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
            <div class="flex items-center gap-6">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Check-in</div>
                    <div class="text-base font-bold text-gray-800 dark:text-white/90">{{ $absensiStatus['jam_masuk'] ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Check-out</div>
                    <div class="text-base font-bold text-gray-800 dark:text-white/90">{{ $absensiStatus['jam_keluar'] ?? '—' }}</div>
                </div>
            </div>
            @php
                $badgeClass = match($absensiStatus['status'] ?? '') {
                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                    'Alpha', 'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                };
            @endphp
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                {{ $absensiStatus['status'] ?? '-' }}
            </span>
        </div>
    @endif

    @if($gpsIsLibur)
        <div class="rounded-lg bg-warning-50 p-4 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
            Hari ini adalah hari libur. Anda tidak dapat melakukan absensi.
        </div>
    @elseif($validasiRadius && $gpsAllowedLocations->isEmpty())
        <div class="rounded-lg bg-error-50 p-4 text-error-700 dark:bg-error-500/10 dark:text-error-400">
            Belum ada titik lokasi absensi yang dikonfigurasi atau diizinkan untuk akun Anda. Silakan hubungi Admin Sekolah untuk menambahkan lokasi di menu Lokasi atau mengatur izin lokasi di Pengaturan Sekolah.
        </div>
    @elseif($sudahCheckin && $sudahCheckout)
        <div class="rounded-lg bg-success-50 px-6 py-4 text-center text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
            Absensi GPS hari ini telah selesai (Check-in &amp; Check-out tercatat).
        </div>
    @else
        <div id="permission-overlay" class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-2xl bg-white/85 backdrop-blur-sm dark:bg-gray-900/85 p-6 text-center">
            <div class="mb-4 rounded-full bg-brand-50 p-4 dark:bg-brand-500/10">
                <svg class="h-10 w-10 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
            </div>
            <h4 class="mb-2 text-lg font-bold text-gray-800 dark:text-white/90">
                {{ $wajibFoto ? 'Izin Akses Lokasi & Kamera' : 'Izin Akses Lokasi GPS' }}
            </h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                {{ $wajibFoto
                    ? 'Untuk melakukan absensi zonasi, sistem membutuhkan izin akses lokasi GPS dan Kamera. Klik tombol di bawah untuk memulai.'
                    : 'Klik tombol di bawah untuk mengaktifkan lokasi GPS' . ($validasiRadius ? ' (foto kamera bersifat opsional).' : ' dalam mode bebas radius.') }}
            </p>
            <button type="button" id="btn-start-permissions" class="rounded-xl bg-brand-500 px-8 py-3 font-semibold text-white shadow-lg transition hover:bg-brand-600">
                Mulai Absensi
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 relative z-10">
            <!-- Map Section -->
            <div class="flex flex-col gap-3 relative">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi Anda</span>
                    <button type="button" id="btn-refresh-location" class="text-sm text-brand-500 hover:text-brand-600">Perbarui Lokasi</button>
                </div>

                <!-- Map Container -->
                <div id="map" class="h-64 w-full rounded-xl border border-gray-200 dark:border-gray-700 z-10"></div>
                <p id="location-status" class="text-sm text-gray-500 dark:text-gray-400">Menunggu izin akses...</p>
            </div>

            <!-- Camera Section -->
            <div class="flex flex-col gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $wajibFoto ? 'Foto Live (Wajib)' : 'Foto Live (Opsional)' }}
                </span>
                <div id="camera-error-msg" class="text-sm text-error-600 dark:text-error-400 empty:hidden"></div>
                <div class="relative flex h-64 w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                    <video id="webcam" autoplay playsinline class="h-full w-full object-cover"></video>
                    <canvas id="canvas" class="hidden h-full w-full object-cover"></canvas>
                    <div id="timestamp-overlay" class="absolute bottom-2 right-2 rounded bg-black/50 px-2 py-1 text-xs text-white"></div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-capture" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Ambil Foto</button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            @if(!$sudahCheckin)
                <button type="button" id="btn-checkin" class="rounded-lg bg-success-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-success-600 focus:ring-4 focus:ring-success-500/20 disabled:cursor-not-allowed disabled:opacity-50" disabled>Check In</button>
            @endif

            @if($sudahCheckin && !$sudahCheckout)
                <button type="button" id="btn-checkout" class="rounded-lg bg-warning-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-warning-600 focus:ring-4 focus:ring-warning-500/20 disabled:cursor-not-allowed disabled:opacity-50" disabled>Check Out</button>
            @endif
        </div>
    @endif
</div>

<!-- CSS for Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- JS for Leaflet & WebRTC -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (!document.getElementById('map')) return;

            const allowedLocations = @json($gpsAllowedLocations);
            const wajibFoto = @json($wajibFoto);
            const validasiRadius = @json($validasiRadius);
            const defaultRadius = @json($defaultRadius);

            const initialCenter = (allowedLocations.length > 0 && allowedLocations[0].latitude && allowedLocations[0].longitude)
                ? [allowedLocations[0].latitude, allowedLocations[0].longitude]
                : [-6.3021, 106.6522];

            let map = L.map('map').setView(initialCenter, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            if (typeof ResizeObserver !== 'undefined') {
                new ResizeObserver(() => map.invalidateSize()).observe(document.getElementById('map'));
            }

            let userMarker = null;
            let userLat = null;
            let userLng = null;
            let locationValid = false;
            let photoData = null;

            // Draw allowed locations circles
            allowedLocations.forEach(loc => {
                if (loc.latitude && loc.longitude) {
                    L.circle([loc.latitude, loc.longitude], {
                        color: 'green',
                        fillColor: '#22c55e',
                        fillOpacity: 0.2,
                        radius: loc.radius || defaultRadius
                    }).addTo(map).bindPopup('Lokasi Diizinkan: ' + loc.nama);
                }
            });

            const locationStatus = document.getElementById('location-status');
            const btnCheckin = document.getElementById('btn-checkin');
            const btnCheckout = document.getElementById('btn-checkout');

            function updateLocation() {
                locationStatus.textContent = 'Mencari lokasi...';
                locationValid = false;
                checkEnableButtons();

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            userLat = position.coords.latitude;
                            userLng = position.coords.longitude;

                            if (userMarker) {
                                userMarker.setLatLng([userLat, userLng]);
                            } else {
                                userMarker = L.marker([userLat, userLng]).addTo(map).bindPopup("Lokasi Anda").openPopup();
                            }

                            map.setView([userLat, userLng], 16);

                            let isWithinRadius = false;
                            let nearestDistance = null;
                            allowedLocations.forEach(loc => {
                                if (loc.latitude && loc.longitude) {
                                    let dist = map.distance([userLat, userLng], [loc.latitude, loc.longitude]);
                                    if (nearestDistance === null || dist < nearestDistance) {
                                        nearestDistance = dist;
                                    }
                                    if (dist <= (loc.radius || defaultRadius)) {
                                        isWithinRadius = true;
                                    }
                                }
                            });

                            if (!validasiRadius) {
                                locationValid = true;
                                locationStatus.innerHTML = '<span class="text-success-600">Lokasi GPS tercatat (Mode Bebas Radius).</span>';
                            } else if (isWithinRadius) {
                                locationValid = true;
                                locationStatus.innerHTML = '<span class="text-success-600">Lokasi valid (di dalam zona sekolah).</span>';
                            } else {
                                locationValid = false;
                                const distInfo = nearestDistance !== null ? ` (jarak terdekat ~${Math.round(nearestDistance)} m)` : '';
                                locationStatus.innerHTML = `<span class="text-error-600">Anda berada di luar zona absensi${distInfo}.</span>`;
                            }
                            checkEnableButtons();
                        },
                        function(error) {
                            locationValid = false;
                            let errMsg = 'Gagal mendapatkan lokasi GPS.';
                            if (error.code === 1) {
                                errMsg = 'Izin lokasi ditolak oleh browser. Silakan klik ikon gembok di sebelah URL bar dan izinkan akses Lokasi (Location).';
                                alert(errMsg);
                            } else if (error.code === 2) {
                                errMsg = 'Sinyal GPS tidak ditemukan.';
                            } else if (error.code === 3) {
                                errMsg = 'Waktu permintaan lokasi habis (timeout).';
                            }
                            locationStatus.innerHTML = '<span class="text-error-600">' + errMsg + '</span>';
                            checkEnableButtons();
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                } else {
                    locationStatus.textContent = 'Geolocation tidak didukung oleh browser ini.';
                }
            }

            document.getElementById('btn-refresh-location')?.addEventListener('click', updateLocation);

            // Webcam Setup
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const timestampOverlay = document.getElementById('timestamp-overlay');
            const btnCapture = document.getElementById('btn-capture');
            const btnStartPermissions = document.getElementById('btn-start-permissions');
            const permissionOverlay = document.getElementById('permission-overlay');
            const errorContainer = document.getElementById('camera-error-msg');

            setInterval(() => {
                if (timestampOverlay) {
                    timestampOverlay.textContent = new Date().toLocaleString('id-ID');
                }
            }, 1000);

            function startWebcam() {
                if (!window.isSecureContext) {
                    if (errorContainer) {
                        errorContainer.innerHTML = "<b>Akses Ditolak:</b> Fitur Kamera dan GPS mewajibkan penggunaan koneksi HTTPS atau localhost.";
                    }
                    if (wajibFoto) {
                        alert("Peringatan: Kamera dan GPS membutuhkan koneksi aman (HTTPS atau localhost).");
                        return;
                    }
                    updateLocation();
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    if (errorContainer) {
                        errorContainer.innerHTML = wajibFoto
                            ? "<b>Error:</b> Browser ini tidak mendukung akses kamera."
                            : "Kamera tidak tersedia di browser ini (foto bersifat opsional).";
                    }
                    if (wajibFoto) {
                        alert("Browser ini tidak mendukung akses kamera.");
                    }
                    updateLocation();
                    return;
                }

                const constraints = {
                    video: { facingMode: { ideal: "user" } },
                    audio: false
                };

                navigator.mediaDevices.getUserMedia(constraints)
                    .then(stream => {
                        video.srcObject = stream;
                        video.classList.remove('hidden');
                        canvas.classList.add('hidden');
                        photoData = null;
                        if (btnCapture) btnCapture.textContent = 'Ambil Foto';
                        if (errorContainer) errorContainer.innerHTML = "";
                        checkEnableButtons();
                        updateLocation();
                    })
                    .catch(err => {
                        console.error("Webcam error:", err);
                        if (errorContainer) {
                            if (!wajibFoto) {
                                errorContainer.innerHTML = "<span class='text-gray-500'>Kamera tidak aktif/tersedia, Anda tetap dapat absen karena foto bersifat opsional.</span>";
                            } else if (err.name === 'NotAllowedError') {
                                errorContainer.innerHTML = "<b>Izin Kamera Ditolak:</b> Silakan izinkan akses Kamera di browser lalu refresh halaman.";
                            } else if (err.name === 'NotFoundError') {
                                errorContainer.innerHTML = "<b>Kamera Tidak Ditemukan:</b> Perangkat Anda tidak memiliki kamera atau kamera sedang dipakai aplikasi lain.";
                            } else {
                                errorContainer.innerHTML = "<b>Gagal Akses Kamera:</b> " + err.message;
                            }
                        }
                        if (wajibFoto) {
                            alert("Gagal mengakses kamera. Silakan cek pesan error di layar.");
                        }
                        updateLocation();
                    });
            }

            if (btnStartPermissions) {
                btnStartPermissions.addEventListener('click', function() {
                    if (permissionOverlay) permissionOverlay.style.display = 'none';
                    setTimeout(() => map.invalidateSize(), 150);
                    startWebcam();
                });
            }

            if (btnCapture) {
                btnCapture.addEventListener('click', function() {
                    if (photoData) {
                        startWebcam();
                        btnCapture.textContent = 'Ambil Foto';
                    } else {
                        if (!video.videoWidth || !video.videoHeight) {
                            alert('Kamera belum siap. Tunggu sebentar atau cek izin kamera.');
                            return;
                        }
                        const context = canvas.getContext('2d');
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;

                        context.drawImage(video, 0, 0, canvas.width, canvas.height);

                        context.fillStyle = "rgba(0, 0, 0, 0.5)";
                        context.fillRect(canvas.width - 250, canvas.height - 40, 240, 30);
                        context.font = "16px Arial";
                        context.fillStyle = "white";
                        context.fillText(timestampOverlay.textContent, canvas.width - 240, canvas.height - 20);

                        photoData = canvas.toDataURL('image/jpeg');

                        video.classList.add('hidden');
                        canvas.classList.remove('hidden');
                        btnCapture.textContent = 'Ambil Ulang Foto';

                        if (video.srcObject) {
                            video.srcObject.getTracks().forEach(track => track.stop());
                        }
                        checkEnableButtons();
                    }
                });
            }

            function checkEnableButtons() {
                const photoOk = wajibFoto ? Boolean(photoData) : true;
                const canSubmit = Boolean(userLat !== null && userLng !== null && locationValid && photoOk);
                if (btnCheckin) btnCheckin.disabled = !canSubmit;
                if (btnCheckout) btnCheckout.disabled = !canSubmit;
            }

            function submitAttendance(type) {
                if (userLat === null || userLng === null) return;
                if (wajibFoto && !photoData) return;

                const btn = type === 'in' ? btnCheckin : btnCheckout;
                if (!btn) return;

                const originalText = btn.textContent;
                btn.textContent = 'Memproses...';
                btn.disabled = true;

                fetch(`/api/gps/check-${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: userLat,
                        longitude: userLng,
                        foto: photoData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Gagal mencatat absensi.'));
                        btn.textContent = originalText;
                        checkEnableButtons();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghubungi server.');
                    btn.textContent = originalText;
                    checkEnableButtons();
                });
            }

            if (btnCheckin) btnCheckin.addEventListener('click', () => submitAttendance('in'));
            if (btnCheckout) btnCheckout.addEventListener('click', () => submitAttendance('out'));

        } catch (e) {
            console.error('GPS Component Error:', e);
            const overlay = document.getElementById('permission-overlay');
            if (overlay) {
                overlay.innerHTML = `<div class="bg-red-100 text-red-700 p-4 rounded-lg w-full max-w-md"><b>Fatal Error:</b> ${e.message}<br>Silakan laporkan masalah ini ke admin.</div>`;
            } else {
                alert("Fatal Error pada komponen GPS: " + e.message);
            }
        }
    });
</script>
