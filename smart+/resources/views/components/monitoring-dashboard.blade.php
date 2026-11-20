@props([
    'data',
    'allSiswaHariIni',
    'pengaturan' => null,
    'kpiGuru',
    'currentMonth',
    'currentYear',
    'isAdminDashboard' => false,
])

<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                {{ $isAdminDashboard ? 'Dashboard Admin' : 'Dashboard Kepala Sekolah' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
                @if($isAdminDashboard)
                    — Tahun Ajaran: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $data['tahun_ajaran_aktif'] ?? '-' }}</span>
                    | Kartu RFID Aktif: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $data['rfid_count'] ?? 0 }}</span>
                @else
                    — Monitoring Kehadiran
                @endif
            </p>
        </div>

        @if($isAdminDashboard)
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('ks.monitoring-guru') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                Monitoring Guru
            </a>
            <a href="{{ route('ks.monitoring-siswa') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60">
                Monitoring Siswa
            </a>
            <a href="{{ route('ks.rekap') }}" class="inline-flex items-center rounded-lg bg-brand-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-600">
                Rekap Absensi
            </a>
        </div>
        @endif
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Guru Hadir</p>
                    <h3 class="mt-1 text-2xl font-semibold text-success-500">{{ $data['guruHadir'] }} <span class="text-sm text-gray-400">/ {{ $data['totalGuru'] }}</span></h3>
                    <p class="mt-1 text-xs text-error-500">
                        {{ $data['guruBelumAbsen'] ?? $data['guruTidakHadir'] }} belum absen
                        @if(($data['guruTidakHadir'] ?? 0) > 0)
                            | {{ $data['guruTidakHadir'] }} alpha
                        @endif
                    </p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
                    <svg class="text-brand-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Siswa Hadir</p>
                    <h3 class="mt-1 text-2xl font-semibold text-success-500">{{ $data['siswaHadir'] }} <span class="text-sm text-gray-400">/ {{ $data['totalSiswa'] }}</span></h3>
                    <p class="mt-1 text-xs text-error-500">{{ $data['siswaAlfa'] }} alfa | {{ $data['siswaTerlambat'] }} terlambat</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                    <svg class="text-success-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Guru Sakit/Izin</p>
                    <h3 class="mt-1 text-2xl font-semibold text-blue-500">{{ $data['guruSakit'] + $data['guruIzin'] }}</h3>
                    <p class="mt-1 text-xs text-gray-400">{{ $data['guruSakit'] }} sakit | {{ $data['guruIzin'] }} izin</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-warning-50 dark:bg-warning-500/10">
                    <svg class="text-warning-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Siswa Sakit/Izin</p>
                    <h3 class="mt-1 text-2xl font-semibold text-purple-500">{{ $data['siswaSakit'] + $data['siswaIzin'] }}</h3>
                    <p class="mt-1 text-xs text-gray-400">{{ $data['siswaSakit'] }} sakit | {{ $data['siswaIzin'] }} izin</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-500/10">
                    <svg class="text-purple-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Monitoring Kehadiran Guru</h3>
            <a href="{{ route('ks.monitoring-guru') }}" class="text-xs font-semibold text-brand-500 hover:text-brand-600">Kelola Monitoring &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">NIP</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Guru</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($data['guruList'] as $i => $guru)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $guru->nip }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('ks.detail-guru', $guru) }}" class="hover:text-brand-500 hover:underline">{{ $guru->nama_lengkap }}</a>
                        </td>
                        <td class="px-6 py-3">
                            @php
                                $statusColor = match($guru->status_tampil) {
                                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                    'Alpha' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                    'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                    default => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $guru->status_tampil }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $guru->jam_masuk ? \Carbon\Carbon::parse($guru->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $guru->jam_keluar ? \Carbon\Carbon::parse($guru->jam_keluar)->format('H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada data guru.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- KPI Target Kehadiran -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">KPI Target Kehadiran Guru (Bulan Ini)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Target hari masuk efektif bulan {{ \Carbon\Carbon::create()->month($currentMonth)->translatedFormat('F') }} {{ $currentYear }}</p>
            </div>
            <div>
                <a href="{{ route('ks.kpi.export', ['bulan' => $currentMonth, 'tahun' => $currentYear]) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Semua Excel
                </a>
            </div>
        </div>

        @if($kpiGuru->isEmpty())
            <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada data guru untuk KPI bulan ini.</p>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($kpiGuru as $guru)
                <div class="relative flex flex-col rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800/50 dark:bg-gray-800/20">
                    <div class="absolute right-3 top-3 flex items-center gap-1">
                        <a href="{{ route('ks.kpi.export', ['bulan' => $currentMonth, 'tahun' => $currentYear, 'guru_id' => $guru->id]) }}" title="Export Excel" class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-success-500 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </a>
                        <button type="button"
                                x-data
                                @click="$dispatch('open-kpi-modal', { id: {{ $guru->id }}, nama: '{{ addslashes($guru->nama_lengkap) }}', target: {{ $guru->kpi_target }} })"
                                title="Edit Target"
                                class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-brand-500 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($guru->foto)
                            <img src="{{ asset('storage/' . $guru->foto) }}" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold uppercase">
                                {{ substr($guru->nama_lengkap, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 line-clamp-1">{{ $guru->nama_lengkap }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">NIP: {{ $guru->nip ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Progress Kehadiran</span>
                            <span class="font-bold {{ $guru->kpi_persentase >= 100 ? 'text-success-500' : 'text-brand-500' }}">{{ $guru->kpi_persentase }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full {{ $guru->kpi_persentase >= 100 ? 'bg-success-500' : 'bg-brand-500' }}" style="width: {{ $guru->kpi_persentase }}%"></div>
                        </div>
                        <div class="mt-2 text-right text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $guru->kpi_hadir }}</span> dari
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $guru->kpi_target }}</span> Hari
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Peta Lokasi Absensi GPS (Guru & Siswa) -->
    @php
        $guruListData = collect($data['guruList'] ?? []);
        $siswaListData = collect($data['siswaList'] ?? []);
        $lokasiSekolahData = collect($data['lokasiSekolah'] ?? []);
        $guruGps = $guruListData->filter(fn($g) => !empty($g->latitude) && !empty($g->longitude))->values();
        $siswaGps = $siswaListData->filter(fn($s) => !empty($s->latitude) && !empty($s->longitude))->values();
        $siswaSorted = $siswaListData->sortBy(fn($s) => [empty($s->status) ? 1 : 0, $s->kelas ?? '', $s->nama_lengkap ?? ''])->values();
    @endphp
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Peta Sebaran Lokasi Absensi GPS (Guru &amp; Siswa)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Pantau titik koordinat check-in GPS Guru (<span class="font-semibold" style="color: #16a34a;">Hijau</span>) dan Siswa (<span class="font-semibold" style="color: #2563eb;">Biru</span>) hari ini.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-map-filter="all" class="map-filter-btn inline-flex items-center gap-1.5 rounded-lg border border-brand-500 bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white transition">
                    Semua ({{ $guruGps->count() + $siswaGps->count() }})
                </button>
                <button type="button" data-map-filter="guru" class="map-filter-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: #16a34a;"></span>
                    Guru ({{ $guruGps->count() }})
                </button>
                <button type="button" data-map-filter="siswa" class="map-filter-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: #2563eb;"></span>
                    Siswa ({{ $siswaGps->count() }})
                </button>
            </div>
        </div>

        <div id="ks-map" class="h-[460px] w-full rounded-xl border border-gray-200 dark:border-gray-700 z-10"></div>

        <div class="mt-4">
            @if($guruGps->isEmpty() && $siswaGps->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 text-center text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400">
                    Belum ada titik absensi GPS Guru maupun Siswa yang terekam hari ini. Marker akan muncul otomatis (<span class="font-semibold" style="color: #16a34a;">#16a34a Hijau untuk Guru</span>, <span class="font-semibold" style="color: #2563eb;">#2563eb Biru untuk Siswa</span>) saat check-in GPS tercatat.
                </div>
            @else
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Daftar Check-In GPS Hari Ini (Klik untuk fokus peta)</h4>
                <div class="grid max-h-56 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($guruGps as $g)
                    <div class="flex items-center justify-between rounded-xl border border-green-200 bg-green-50/50 p-3 dark:border-green-500/20 dark:bg-green-500/5">
                        <button type="button" onclick="window.focusAttendanceMarker && window.focusAttendanceMarker('guru-{{ $g->id }}')" class="text-left">
                            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-800 dark:text-white/90">
                                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: #16a34a;"></span>
                                {{ $g->nama_lengkap }}
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Guru &bull; {{ $g->jam_masuk ? \Carbon\Carbon::parse($g->jam_masuk)->format('H:i') : '-' }}
                                @if(!empty($g->nama_lokasi)) &bull; {{ $g->nama_lokasi }} @endif
                            </div>
                            <div class="mt-0.5 font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                {{ number_format((float) $g->latitude, 5) }}, {{ number_format((float) $g->longitude, 5) }}
                            </div>
                        </button>
                        <a href="https://maps.google.com/?q={{ $g->latitude }},{{ $g->longitude }}" target="_blank" rel="noopener" class="ml-2 shrink-0 rounded-lg border border-green-300 bg-white px-2 py-1 text-[11px] font-medium text-green-700 hover:bg-green-50 dark:border-green-700 dark:bg-gray-800 dark:text-green-400">
                            Maps
                        </a>
                    </div>
                    @endforeach
                    @foreach($siswaGps as $s)
                    <div class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50/50 p-3 dark:border-blue-500/20 dark:bg-blue-500/5">
                        <button type="button" onclick="window.focusAttendanceMarker && window.focusAttendanceMarker('siswa-{{ $s->siswa_id }}')" class="text-left">
                            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-800 dark:text-white/90">
                                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: #2563eb;"></span>
                                {{ $s->nama_lengkap }}
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Siswa ({{ $s->kelas }}) &bull; {{ $s->jam_masuk ? \Carbon\Carbon::parse($s->jam_masuk)->format('H:i') : '-' }}
                                @if(!empty($s->nama_lokasi)) &bull; {{ $s->nama_lokasi }} @endif
                            </div>
                            <div class="mt-0.5 font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                {{ number_format((float) $s->latitude, 5) }}, {{ number_format((float) $s->longitude, 5) }}
                            </div>
                        </button>
                        <a href="https://maps.google.com/?q={{ $s->latitude }},{{ $s->longitude }}" target="_blank" rel="noopener" class="ml-2 shrink-0 rounded-lg border border-blue-300 bg-white px-2 py-1 text-[11px] font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:bg-gray-800 dark:text-blue-400">
                            Maps
                        </a>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Monitoring Kehadiran Siswa Hari Ini (Always Visible) -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Monitoring Kehadiran Siswa Hari Ini</h3>
            <a href="{{ route('ks.monitoring-siswa') }}" class="text-xs font-semibold text-brand-500 hover:text-brand-600">Kelola Monitoring Siswa &rarr;</a>
        </div>
        <div class="max-h-[420px] overflow-x-auto overflow-y-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-900">
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">NISN</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Siswa</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Lokasi GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($siswaSorted as $i => $s)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $s->nisn }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">
                            <a href="{{ route('ks.detail-siswa', $s->siswa_id) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $s->nama_lengkap }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $s->kelas }}</td>
                        <td class="px-6 py-3">
                            @php
                                $sBadge = match($s->status_tampil) {
                                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                    'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                    'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sBadge }}">{{ $s->status_tampil }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $s->jam_masuk ? \Carbon\Carbon::parse($s->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $s->jam_keluar ? \Carbon\Carbon::parse($s->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-xs">
                            @if(!empty($s->latitude) && !empty($s->longitude))
                                <a href="https://maps.google.com/?q={{ $s->latitude }},{{ $s->longitude }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    <span class="inline-block h-2 w-2 rounded-full" style="background-color: #2563eb;"></span>
                                    {{ $s->nama_lokasi ?: (number_format((float) $s->latitude, 4) . ', ' . number_format((float) $s->longitude, 4)) }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data siswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rekap Kehadiran Siswa Per Kelas (Always Visible) -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Rekap Kehadiran Siswa Per Kelas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Hadir</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Terlambat</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Alfa</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Sakit</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Izin</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Belum Absen</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($data['rekapPerKelas'] as $rekap)
                    @php $persentase = $rekap->total > 0 ? round(($rekap->hadir / $rekap->total) * 100, 1) : 0; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $rekap->kelas }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $rekap->total }}</td>
                        <td class="px-6 py-3 text-success-600 font-medium">{{ $rekap->hadir }}</td>
                        <td class="px-6 py-3 text-warning-600">{{ $rekap->terlambat }}</td>
                        <td class="px-6 py-3 text-error-600">{{ $rekap->alfa }}</td>
                        <td class="px-6 py-3 text-blue-600">{{ $rekap->sakit }}</td>
                        <td class="px-6 py-3 text-purple-600">{{ $rekap->izin }}</td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $rekap->belum_absen ?? 0 }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-full rounded-full {{ $persentase >= 80 ? 'bg-success-500' : ($persentase >= 60 ? 'bg-warning-500' : 'bg-error-500') }}" style="width: {{ $persentase }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $persentase }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data kelas siswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detail Kehadiran Siswa Per Kelas (Always Visible) -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Detail Kehadiran Siswa Per Kelas</h3>
            <a href="{{ route('ks.monitoring-siswa') }}" class="text-xs font-semibold text-brand-500 hover:text-brand-600">Kelola Monitoring Siswa &rarr;</a>
        </div>
        <div class="max-h-[520px] overflow-y-auto p-6">
            @forelse($allSiswaHariIni as $kelas => $siswas)
            <div class="mb-6 last:mb-0">
                <h4 class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kelas {{ $kelas }}</h4>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($siswas as $s)
                    @php
                        $statusColor = match($s->status) {
                            'Hadir' => 'border-success-200 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/5',
                            'Terlambat' => 'border-warning-200 bg-warning-50/50 dark:border-warning-500/20 dark:bg-warning-500/5',
                            'Alfa' => 'border-error-200 bg-error-50/50 dark:border-error-500/20 dark:bg-error-500/5',
                            'Sakit' => 'border-blue-200 bg-blue-50/50 dark:border-blue-500/20 dark:bg-blue-500/5',
                            'Izin' => 'border-purple-200 bg-purple-50/50 dark:border-purple-500/20 dark:bg-purple-500/5',
                            default => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50',
                        };
                        $statusBadge = match($s->status) {
                            'Hadir' => 'bg-success-500 text-white',
                            'Terlambat' => 'bg-warning-500 text-white',
                            'Alfa' => 'bg-error-500 text-white',
                            'Sakit' => 'bg-blue-500 text-white',
                            'Izin' => 'bg-purple-500 text-white',
                            default => 'bg-gray-400 text-white',
                        };
                    @endphp
                    <a href="{{ route('ks.detail-siswa', $s->siswa_id) }}" class="flex items-center justify-between rounded-xl border p-3 transition hover:shadow-md {{ $statusColor }}">
                        <div>
                            <div class="font-medium text-gray-800 dark:text-white/90 text-sm">{{ $s->nama_lengkap }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $s->nisn }} | {{ $s->jam_masuk ? \Carbon\Carbon::parse($s->jam_masuk)->format('H:i') : '-' }}
                                @if(!empty($s->latitude) && !empty($s->longitude))
                                    | <span class="font-medium text-blue-600 dark:text-blue-400">GPS</span>
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $statusBadge }}">{{ $s->status ?? 'Belum Absen' }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @empty
            <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada data detail siswa per kelas.</p>
            @endforelse
        </div>
    </div>

    @if($isAdminDashboard && isset($data['absensi_siswa_terbaru']) && $data['absensi_siswa_terbaru']->isNotEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Siswa Hari Ini (Log Terbaru)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Siswa</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @foreach($data['absensi_siswa_terbaru'] as $i => $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $abs->siswa->nama_lengkap ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->siswa->kelas ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jadwal?->mataPelajaran?->nama ?? ($abs->jadwal_id ? '-' : 'Absensi Harian') }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) {
                                'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                default => 'bg-gray-50 text-gray-700',
                            }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Leaflet JS for monitoring Guru (Hijau #16a34a) & Siswa (Biru #2563eb) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const guruList = @json($data['guruList'] ?? []);
        const siswaList = @json($data['siswaList'] ?? []);
        const lokasiSekolah = @json($data['lokasiSekolah'] ?? []);

        const mapEl = document.getElementById('ks-map');
        if (!mapEl || typeof L === 'undefined') return;

        const escapeHtml = (value) => String(value ?? '-')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        let defaultLat = -6.3021;
        let defaultLng = 106.6522;
        if (lokasiSekolah.length > 0 && lokasiSekolah[0].latitude && lokasiSekolah[0].longitude) {
            defaultLat = parseFloat(lokasiSekolah[0].latitude);
            defaultLng = parseFloat(lokasiSekolah[0].longitude);
        }

        const map = L.map('ks-map').setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const zoneLayer = L.layerGroup().addTo(map);
        const guruLayer = L.layerGroup().addTo(map);
        const siswaLayer = L.layerGroup().addTo(map);

        const placedCoords = [];
        const zoneCoords = [];
        const markerRegistry = {};

        lokasiSekolah.forEach((loc) => {
            if (loc.latitude && loc.longitude) {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                const radius = parseInt(loc.radius || 100, 10);
                if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                    L.circle([lat, lng], {
                        radius: radius,
                        color: '#6b7280',
                        fillColor: '#9ca3af',
                        fillOpacity: 0.12,
                        weight: 1.5,
                        dashArray: '4 4'
                    }).bindPopup(`<strong>Zona Sekolah: ${escapeHtml(loc.nama)}</strong><br>Radius: ${radius} meter`).addTo(zoneLayer);
                    zoneCoords.push([lat, lng]);
                }
            }
        });

        const jitterIfOverlap = (lat, lng) => {
            const overlaps = placedCoords.some(c => Math.abs(c[0] - lat) < 0.00005 && Math.abs(c[1] - lng) < 0.00005);
            if (overlaps) {
                return [
                    lat + (Math.random() - 0.5) * 0.0002,
                    lng + (Math.random() - 0.5) * 0.0002
                ];
            }
            return [lat, lng];
        };

        guruList.forEach((guru) => {
            if (guru.latitude && guru.longitude) {
                let lat = parseFloat(guru.latitude);
                let lng = parseFloat(guru.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                const rawLat = lat;
                const rawLng = lng;
                [lat, lng] = jitterIfOverlap(lat, lng);
                placedCoords.push([lat, lng]);

                const marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#16a34a',
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }).addTo(guruLayer);

                const photoHtml = guru.foto_lokasi
                    ? `<br><img src="/storage/${encodeURI(guru.foto_lokasi)}" class="mt-2 h-24 w-auto rounded" alt="Foto Lokasi">`
                    : '';

                marker.bindPopup(`
                    <div class="text-xs leading-relaxed">
                        <span style="display:inline-block;padding:2px 6px;border-radius:9999px;background:#dcfce7;color:#15803d;font-weight:700;margin-bottom:4px;">GURU</span><br>
                        <strong>${escapeHtml(guru.nama_lengkap)}</strong><br>
                        NIP: ${escapeHtml(guru.nip)}<br>
                        Status: ${escapeHtml(guru.status_tampil)}<br>
                        Masuk: ${escapeHtml(guru.jam_masuk)}<br>
                        ${guru.nama_lokasi ? `Zona: ${escapeHtml(guru.nama_lokasi)}<br>` : ''}
                        <a href="https://maps.google.com/?q=${rawLat},${rawLng}" target="_blank" rel="noopener" style="color:#16a34a;text-decoration:underline;">Buka di Google Maps</a>
                        ${photoHtml}
                    </div>
                `);

                markerRegistry[`guru-${guru.id}`] = { marker, lat, lng, group: 'guru' };
            }
        });

        siswaList.forEach((siswa) => {
            if (siswa.latitude && siswa.longitude) {
                let lat = parseFloat(siswa.latitude);
                let lng = parseFloat(siswa.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                const rawLat = lat;
                const rawLng = lng;
                [lat, lng] = jitterIfOverlap(lat, lng);
                placedCoords.push([lat, lng]);

                const marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#2563eb',
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }).addTo(siswaLayer);

                const photoHtml = siswa.foto_lokasi
                    ? `<br><img src="/storage/${encodeURI(siswa.foto_lokasi)}" class="mt-2 h-24 w-auto rounded" alt="Foto Lokasi">`
                    : '';

                marker.bindPopup(`
                    <div class="text-xs leading-relaxed">
                        <span style="display:inline-block;padding:2px 6px;border-radius:9999px;background:#dbeafe;color:#1d4ed8;font-weight:700;margin-bottom:4px;">SISWA</span><br>
                        <strong>${escapeHtml(siswa.nama_lengkap)}</strong><br>
                        NISN: ${escapeHtml(siswa.nisn)} (${escapeHtml(siswa.kelas)})<br>
                        Status: ${escapeHtml(siswa.status_tampil)}<br>
                        Masuk: ${escapeHtml(siswa.jam_masuk)}<br>
                        ${siswa.nama_lokasi ? `Zona: ${escapeHtml(siswa.nama_lokasi)}<br>` : ''}
                        <a href="https://maps.google.com/?q=${rawLat},${rawLng}" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:underline;">Buka di Google Maps</a>
                        ${photoHtml}
                    </div>
                `);

                markerRegistry[`siswa-${siswa.siswa_id}`] = { marker, lat, lng, group: 'siswa' };
            }
        });

        const fitActiveBounds = (filter) => {
            const pts = [];
            Object.values(markerRegistry).forEach((entry) => {
                if (filter === 'all' || entry.group === filter) {
                    pts.push([entry.lat, entry.lng]);
                }
            });
            if (pts.length > 0) {
                map.fitBounds(pts, { padding: [50, 50], maxZoom: 17 });
            } else if (zoneCoords.length > 0) {
                map.fitBounds(zoneCoords, { padding: [50, 50], maxZoom: 16 });
            }
        };

        fitActiveBounds('all');

        const filterButtons = document.querySelectorAll('.map-filter-btn');
        const applyFilter = (filter) => {
            if (filter === 'all') {
                if (!map.hasLayer(guruLayer)) map.addLayer(guruLayer);
                if (!map.hasLayer(siswaLayer)) map.addLayer(siswaLayer);
            } else if (filter === 'guru') {
                if (!map.hasLayer(guruLayer)) map.addLayer(guruLayer);
                if (map.hasLayer(siswaLayer)) map.removeLayer(siswaLayer);
            } else if (filter === 'siswa') {
                if (map.hasLayer(guruLayer)) map.removeLayer(guruLayer);
                if (!map.hasLayer(siswaLayer)) map.addLayer(siswaLayer);
            }

            filterButtons.forEach((btn) => {
                const active = btn.dataset.mapFilter === filter;
                btn.classList.toggle('bg-brand-500', active);
                btn.classList.toggle('dark:bg-brand-500', active);
                btn.classList.toggle('border-brand-500', active);
                btn.classList.toggle('dark:border-brand-500', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('dark:text-white', active);
                btn.classList.toggle('bg-white', !active);
                btn.classList.toggle('dark:bg-gray-800', !active);
                btn.classList.toggle('border-gray-200', !active);
                btn.classList.toggle('dark:border-gray-700', !active);
                btn.classList.toggle('text-gray-700', !active);
                btn.classList.toggle('dark:text-gray-200', !active);
            });

            fitActiveBounds(filter);
        };

        filterButtons.forEach((btn) => {
            btn.addEventListener('click', () => applyFilter(btn.dataset.mapFilter || 'all'));
        });

        window.focusAttendanceMarker = (key) => {
            const entry = markerRegistry[key];
            if (!entry) return;
            applyFilter('all');
            map.setView([entry.lat, entry.lng], 17, { animate: true });
            entry.marker.openPopup();
            mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };
    });
</script>

<!-- Modal Update KPI -->
<div x-data="{ open: false, id: '', nama: '', target: 0 }"
     @open-kpi-modal.window="open = true; id = $event.detail.id; nama = $event.detail.nama; target = $event.detail.target"
     class="relative z-50"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true"
     x-show="open"
     x-cloak>

    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/80 backdrop-blur-sm"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md dark:bg-gray-800 border border-gray-200 dark:border-gray-700">

                <form action="{{ route('ks.kpi.update-target') }}" method="POST">
                    @csrf
                    <input type="hidden" name="guru_id" x-model="id">
                    <input type="hidden" name="bulan" value="{{ $currentMonth }}">
                    <input type="hidden" name="tahun" value="{{ $currentYear }}">

                    <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 sm:mx-0 sm:h-10 sm:w-10 dark:bg-brand-500/20">
                                <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Update Target KPI Kehadiran</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Atur target hari masuk untuk guru <strong x-text="nama" class="text-gray-700 dark:text-gray-300"></strong> pada bulan ini.</p>

                                    <div class="mt-4">
                                        <label for="target_hari" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target (Hari)</label>
                                        <input type="number" x-model="target" name="target_hari" id="target_hari" min="0" max="31" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-800/50">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">Simpan Target</button>
                        <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-700">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
