@extends('layouts.app')

@section('content')
@php
    $nowWib = \Carbon\Carbon::now('Asia/Jakarta');
    $hariMap = [
        0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
    ];
    $namaHariIni = $hariMap[$nowWib->dayOfWeek] ?? 'Senin';
    $jamNowWib = $nowWib->format('H:i:s');
    $tanggalHariIni = $nowWib->translatedFormat('l, d F Y');

    $hariOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $groupedJadwals = $jadwals->groupBy('hari')->sortBy(function ($item, $key) use ($hariOrder) {
        $pos = array_search($key, $hariOrder);
        return $pos === false ? 99 : $pos;
    });
    $jadwalHariIni = ($groupedJadwals[$namaHariIni] ?? collect())->sortBy('jam_mulai')->values();
    $availableDays = $groupedJadwals->keys()->values();

    $totalHadirAll = 0;
    $totalTerlambatAll = 0;
    $totalSesiAll = 0;
    foreach ($rekapAbsensi as $rk) {
        $totalHadirAll += (int) ($rk['Hadir'] ?? 0);
        $totalTerlambatAll += (int) ($rk['Terlambat'] ?? 0);
        $totalSesiAll += (int) ($rk['Total'] ?? 0);
    }
    $rataKehadiran = $totalSesiAll > 0 ? round((($totalHadirAll + $totalTerlambatAll) / $totalSesiAll) * 100) : 0;

    $jamMasukBase = \Carbon\Carbon::parse($nowWib->toDateString() . ' ' . ($pengaturan->jam_masuk ?? '07:15:00'), 'Asia/Jakarta');
    $tolTerlambat = (int) ($pengaturan->toleransi_terlambat_menit ?? 15);
    $tolAlphaRaw = (int) ($pengaturan->toleransi_alpha_menit ?? 30);
    $tolAlpha = $tolAlphaRaw > $tolTerlambat ? $tolAlphaRaw : ($tolTerlambat + max(15, $tolAlphaRaw));
    $batasHadirStr = $jamMasukBase->copy()->addMinutes($tolTerlambat)->format('H:i');
    $batasAlphaStr = $jamMasukBase->copy()->addMinutes($tolAlpha)->format('H:i');

    $sudahCheckIn = (bool) ($absensiStatus['sudah_check_in'] ?? false);
    $sudahCheckOut = (bool) ($absensiStatus['sudah_check_out'] ?? false);
    $statusHariIniLabel = $absensiStatus['status'] ?? (($isLibur ?? false) ? 'Hari Libur' : 'Belum Absen');

    $statusPillClass = match ($statusHariIniLabel) {
        'Hadir' => 'bg-[#E4FBE7] text-[#0B7B3E] ring-1 ring-inset ring-[#0B7B3E]/20 dark:bg-[#20C933]/15 dark:text-[#4AE05C] dark:ring-[#20C933]/30',
        'Terlambat' => 'bg-[#FFF6D6] text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057] dark:ring-[#FCB400]/30',
        'Alfa', 'Alpha' => 'bg-[#FFE8EE] text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096] dark:ring-[#F82B60]/30',
        'Izin', 'Sakit' => 'bg-[#EBF3FF] text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF] dark:ring-[#166EE1]/30',
        default => 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-300/70 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
    };

    $statusCardBorder = match ($statusHariIniLabel) {
        'Hadir' => 'border-l-emerald-500',
        'Terlambat' => 'border-l-amber-500',
        'Alfa', 'Alpha' => 'border-l-rose-500',
        'Izin', 'Sakit' => 'border-l-[#166EE1]',
        default => 'border-l-gray-400 dark:border-l-gray-600',
    };

    $gpsAktif = in_array($metode ?? 'RFID', ['Zonasi', 'Hybrid']) && !($isLibur ?? false);
    $siswaInitials = collect(explode(' ', trim($siswa->nama_lengkap ?? 'S')))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<div
    class="mx-auto max-w-screen-xl px-4 pt-4 pb-24 sm:px-6 md:pt-6 md:pb-10"
    x-data="{
        activeTab: ['jadwal', 'tugas', 'absensi'].includes(new URLSearchParams(window.location.search).get('tab'))
            ? new URLSearchParams(window.location.search).get('tab')
            : 'jadwal',
        selectedDay: 'semua',
        tugasFilter: 'semua',
        scheduleView: 'grid',
        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
    }"
>
    <!-- Airtable Base Identity Header Card -->
    <header class="mb-6 overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06),0_1px_2px_rgba(16,24,40,0.04)] dark:border-gray-800 dark:bg-gray-900">
        <div class="h-1.5 w-full bg-[#166EE1]"></div>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#EBF3FF] text-base font-bold text-[#166EE1] ring-1 ring-inset ring-[#166EE1]/20 sm:h-14 sm:w-14 sm:text-lg dark:bg-[#166EE1]/20 dark:text-[#4EA8FF]">
                    {{ $siswaInitials ?: 'SW' }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <time datetime="{{ $nowWib->toDateString() }}">{{ $tanggalHariIni }}</time>
                        </span>
                        <span class="inline-flex items-center rounded-md bg-[#EBF3FF] px-2 py-0.5 font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                            Kelas {{ $siswa->kelas }}
                        </span>
                    </div>

                    <h1 class="mt-1.5 break-words text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                        {{ $siswa->nama_lengkap }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100/90 px-2.5 py-1 font-medium tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <span class="text-gray-500 dark:text-gray-400">NISN:</span>
                            <strong class="font-semibold text-gray-900 dark:text-white">{{ $siswa->nisn ?? '-' }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-[#F1E9FF] px-2.5 py-1 font-medium text-[#5D2BC2] ring-1 ring-inset ring-[#5D2BC2]/15 dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]">
                            <span>Metode Absensi:</span>
                            <strong class="font-semibold">{{ $metode ?? 'RFID' }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            @if($gpsAktif && !$sudahCheckOut)
                <div class="shrink-0">
                    <button
                        type="button"
                        @click="switchTab('absensi')"
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#166EE1] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-11a6 6 0 1112 0c0 5.314-6 11-6 11z" />
                            <circle cx="12" cy="10" r="2.25" />
                        </svg>
                        <span>{{ $sudahCheckIn ? 'Lanjut Check-Out GPS' : 'Absen GPS Sekarang' }}</span>
                    </button>
                </div>
            @endif
        </div>
    </header>

    <!-- 4 Tactile Airtable Metric Cards -->
    <section aria-label="Ringkasan aktivitas siswa" class="mb-6 grid grid-cols-2 gap-3.5 sm:gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200/90 border-l-4 {{ $statusCardBorder }} bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Absensi hari ini</span>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#E4FBE7] text-[#0B7B3E] dark:bg-[#20C933]/15 dark:text-[#4AE05C]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $statusPillClass }}">
                    {{ $statusHariIniLabel }}
                </span>
                @if($sudahCheckIn && !empty($absensiStatus['jam_masuk']))
                    <span class="text-sm font-bold tabular-nums text-gray-900 dark:text-white">{{ $absensiStatus['jam_masuk'] }}</span>
                @endif
            </div>
            <p class="mt-2 text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                Tepat waktu s/d {{ $batasHadirStr }}, batas {{ $batasAlphaStr }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#166EE1] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Jadwal hari {{ strtolower($namaHariIni) }}</span>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#EBF3FF] text-[#166EE1] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">
                {{ $jadwalHariIni->count() }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">mapel</span>
            </div>
            <p class="mt-2 text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                Total {{ $jadwals->count() }} sesi dalam seminggu
            </p>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#F82B60] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tugas belum dikumpul</span>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#FFE8EE] text-[#C91B4B] dark:bg-[#F82B60]/15 dark:text-[#FF7096]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold tabular-nums {{ ($unreadTugasCount ?? 0) > 0 ? 'text-[#C91B4B] dark:text-[#FF7096]' : 'text-gray-900 dark:text-white' }}">
                {{ $unreadTugasCount ?? 0 }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">tugas</span>
            </div>
            <p class="mt-2 text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                Dari {{ $tugasTerbaru->count() }} penugasan terbaru
            </p>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#067A6F] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tingkat kehadiran</span>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 3 5-6" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $rataKehadiran }}%</span>
                <span class="text-[11px] tabular-nums text-gray-500 dark:text-gray-400">{{ $totalHadirAll + $totalTerlambatAll }}/{{ $totalSesiAll }}</span>
            </div>
            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-full rounded-full {{ $rataKehadiran >= 85 ? 'bg-emerald-500' : ($rataKehadiran >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $rataKehadiran }}%"></div>
            </div>
        </div>
    </section>

    <!-- Airtable Sunken-Tray View Switcher Toolbar -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <nav aria-label="Navigasi menu siswa" class="inline-flex w-full overflow-x-auto rounded-xl bg-gray-100/90 p-1 ring-1 ring-inset ring-gray-200/80 no-scrollbar sm:w-auto dark:bg-gray-800/90 dark:ring-gray-700/80">
            <button
                type="button"
                @click="switchTab('jadwal')"
                :aria-selected="activeTab === 'jadwal'"
                :class="activeTab === 'jadwal'
                    ? 'bg-white text-[#166EE1] shadow-sm ring-1 ring-gray-200/70 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-gray-700'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[40px] flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:flex-initial sm:text-sm"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18" />
                </svg>
                <span>Jadwal Pelajaran</span>
            </button>

            <button
                type="button"
                @click="switchTab('tugas')"
                :aria-selected="activeTab === 'tugas'"
                :class="activeTab === 'tugas'
                    ? 'bg-white text-[#166EE1] shadow-sm ring-1 ring-gray-200/70 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-gray-700'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[40px] flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:flex-initial sm:text-sm"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Tugas</span>
                @if(isset($unreadTugasCount) && $unreadTugasCount > 0)
                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#F82B60] px-1.5 text-[11px] font-bold tabular-nums text-white">
                        {{ $unreadTugasCount }}
                    </span>
                @endif
            </button>

            <button
                type="button"
                @click="switchTab('absensi')"
                :aria-selected="activeTab === 'absensi'"
                :class="activeTab === 'absensi'
                    ? 'bg-white text-[#166EE1] shadow-sm ring-1 ring-gray-200/70 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-gray-700'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[40px] flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:flex-initial sm:text-sm"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Absensi &amp; Rekap</span>
            </button>
        </nav>

        <!-- Desktop View Switcher (Grid vs Gallery Cards) for Jadwal -->
        <div x-show="activeTab === 'jadwal'" class="hidden items-center gap-1 rounded-xl bg-gray-100/90 p-1 ring-1 ring-inset ring-gray-200/80 md:inline-flex dark:bg-gray-800/90 dark:ring-gray-700/80">
            <button
                type="button"
                @click="scheduleView = 'grid'"
                :class="scheduleView === 'grid' ? 'bg-white text-[#166EE1] shadow-sm dark:bg-gray-900 dark:text-[#4EA8FF]' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M3 6h18M3 12h18M3 18h18M9 6v12" />
                </svg>
                <span>Tampilan Tabel</span>
            </button>
            <button
                type="button"
                @click="scheduleView = 'cards'"
                :class="scheduleView === 'cards' ? 'bg-white text-[#166EE1] shadow-sm dark:bg-gray-900 dark:text-[#4EA8FF]' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                </svg>
                <span>Tampilan Kartu</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: JADWAL PELAJARAN -->
    <section x-show="activeTab === 'jadwal'" aria-label="Jadwal pelajaran siswa">
        <!-- Today's Schedule Priority Cards -->
        <div class="mb-8">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#166EE1]"></span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Jadwal Hari Ini ({{ $namaHariIni }})</h2>
                </div>
                <span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $jadwalHariIni->count() }} sesi
                </span>
            </div>

            @if($jadwalHariIni->isNotEmpty())
                <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($jadwalHariIni as $jadwal)
                        @php
                            $mulai = substr($jadwal->jam_mulai, 0, 5);
                            $selesai = substr($jadwal->jam_selesai, 0, 5);
                            $isOngoing = $jamNowWib >= $jadwal->jam_mulai && $jamNowWib <= $jadwal->jam_selesai;
                            $isDone = $jamNowWib > $jadwal->jam_selesai;
                            $leftAccent = $isOngoing ? 'border-l-emerald-500' : ($isDone ? 'border-l-gray-300 dark:border-l-gray-700' : 'border-l-[#166EE1]');
                            $guruNama = $jadwal->guru->nama_lengkap ?? '-';
                            $guruInit = collect(explode(' ', trim($guruNama)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
                        @endphp
                        <a
                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                            class="group flex flex-col justify-between rounded-xl border border-gray-200/90 border-l-4 {{ $leftAccent }} bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] transition-colors duration-150 hover:border-[#166EE1]/45 hover:shadow-[0_4px_12px_rgba(22,110,225,0.08)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-800 dark:bg-gray-900 dark:hover:border-[#4EA8FF]/45"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        <svg class="h-3.5 w-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" />
                                            <path stroke-linecap="round" d="M12 7v5l3 2" />
                                        </svg>
                                        <span>{{ $mulai }} - {{ $selesai }}</span>
                                    </span>
                                    @if($isOngoing)
                                        <span class="inline-flex rounded-md bg-[#E4FBE7] px-2 py-0.5 text-xs font-semibold text-[#0B7B3E] ring-1 ring-inset ring-[#0B7B3E]/20 dark:bg-[#20C933]/15 dark:text-[#4AE05C]">
                                            Sedang Berlangsung
                                        </span>
                                    @elseif($isDone)
                                        <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-md bg-[#EBF3FF] px-2 py-0.5 text-xs font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                                            Terjadwal
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-3 text-base font-bold text-gray-900 group-hover:text-[#166EE1] dark:text-white dark:group-hover:text-[#4EA8FF]">
                                    {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                </h3>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                        {{ $guruInit }}
                                    </span>
                                    <span class="truncate text-xs font-medium text-gray-600 dark:text-gray-300">{{ $guruNama }}</span>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-md bg-[#F1E9FF] px-2 py-0.5 text-[11px] font-semibold text-[#5D2BC2] dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]">
                                    {{ $jadwal->lokasi->nama ?? '-' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-5 py-8 text-center dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Tidak ada jadwal kelas pada hari {{ $namaHariIni }}.</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lihat jadwal mingguan di bawah untuk mengakses materi dan tugas dari mata pelajaran lainnya.</p>
                </div>
            @endif
        </div>

        <!-- Weekly Schedule Filter Toolbar -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Jadwal Mingguan Lengkap</h2>
            @if($availableDays->isNotEmpty())
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar" role="group" aria-label="Filter hari jadwal">
                    <button
                        type="button"
                        @click="selectedDay = 'semua'"
                        :class="selectedDay === 'semua'
                            ? 'bg-[#166EE1] text-white shadow-2xs ring-1 ring-[#166EE1]'
                            : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                        class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                    >
                        Semua Hari
                    </button>
                    @foreach($availableDays as $dayLabel)
                        <button
                            type="button"
                            @click="selectedDay = '{{ $dayLabel }}'"
                            :class="selectedDay === '{{ $dayLabel }}'
                                ? 'bg-[#166EE1] text-white shadow-2xs ring-1 ring-[#166EE1]'
                                : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                            class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                        >
                            {{ $dayLabel }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @forelse($groupedJadwals as $hari => $jadwalsHari)
            @php $sortedDayJadwals = $jadwalsHari->sortBy('jam_mulai')->values(); @endphp
            <div x-show="selectedDay === 'semua' || selectedDay === '{{ $hari }}'" class="mb-6 overflow-hidden rounded-xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50/90 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-800/60">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-sm bg-[#166EE1]"></span>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $hari }}</h3>
                    </div>
                    <span class="rounded-md bg-white px-2 py-0.5 text-xs font-semibold tabular-nums text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
                        {{ $sortedDayJadwals->count() }} mata pelajaran
                    </span>
                </div>

                <!-- Airtable Grid Table View (Desktop when scheduleView === 'grid') -->
                <div x-show="scheduleView === 'grid'" class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50/50 text-xs font-semibold text-gray-600 dark:border-gray-800 dark:bg-gray-800/30 dark:text-gray-400">
                                <th class="w-12 px-4 py-3 text-center tabular-nums">#</th>
                                <th class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                                        <span>Waktu</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-[#166EE1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        <span>Mata Pelajaran</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Guru Pengampu</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-[#5D2BC2]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-11a6 6 0 1112 0c0 5.314-6 11-6 11z"/><circle cx="12" cy="10" r="2.25"/></svg>
                                        <span>Ruang / Lokasi</span>
                                    </span>
                                </th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($sortedDayJadwals as $idx => $jadwal)
                                @php
                                    $gNama = $jadwal->guru->nama_lengkap ?? '-';
                                    $gInit = collect(explode(' ', trim($gNama)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
                                @endphp
                                <tr class="transition-colors duration-150 hover:bg-[#EBF3FF]/40 dark:hover:bg-[#166EE1]/10">
                                    <td class="px-4 py-3.5 text-center text-xs font-medium tabular-nums text-gray-400">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                            {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex rounded-md bg-[#EBF3FF] px-2.5 py-1 text-xs font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/15 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                                            {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                                {{ $gInit }}
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $gNama }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex rounded-md bg-[#F1E9FF] px-2 py-0.5 text-xs font-semibold text-[#5D2BC2] dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]">
                                            {{ $jadwal->lokasi->nama ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3.5 text-right">
                                        <a
                                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#166EE1] shadow-2xs transition-colors duration-150 hover:border-[#166EE1] hover:bg-[#EBF3FF] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-700 dark:bg-gray-800 dark:text-[#4EA8FF]"
                                        >
                                            <span>Detail Kelas</span>
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Airtable Gallery Card View (Always on Mobile, or Desktop when scheduleView === 'cards') -->
                <div :class="scheduleView === 'cards' ? 'grid grid-cols-1 gap-3.5 p-4 md:grid-cols-2 lg:grid-cols-3' : 'divide-y divide-gray-200 md:hidden dark:divide-gray-800'">
                    @foreach($sortedDayJadwals as $jadwal)
                        @php
                            $gNamaM = $jadwal->guru->nama_lengkap ?? '-';
                            $gInitM = collect(explode(' ', trim($gNamaM)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
                        @endphp
                        <a
                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                            :class="scheduleView === 'cards' ? 'rounded-xl border border-gray-200 border-l-4 border-l-[#166EE1] p-4 shadow-2xs hover:border-[#166EE1] dark:border-gray-800' : 'px-4 py-3.5 active:bg-gray-50 dark:active:bg-gray-800/60'"
                            class="flex items-center justify-between gap-3 bg-white transition-colors duration-150 dark:bg-gray-900"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                                    </span>
                                    <span class="inline-flex rounded bg-[#F1E9FF] px-1.5 py-0.5 text-[11px] font-semibold text-[#5D2BC2] dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]">
                                        {{ $jadwal->lokasi->nama ?? '-' }}
                                    </span>
                                </div>
                                <h4 class="mt-1.5 truncate text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                </h4>
                                <div class="mt-1.5 flex items-center gap-1.5">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[9px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                        {{ $gInitM }}
                                    </span>
                                    <span class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $gNamaM }}</span>
                                </div>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Belum ada jadwal pelajaran</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jadwal pelajaran untuk kelas {{ $siswa->kelas }} pada tahun ajaran aktif belum tersedia.</p>
            </div>
        @endforelse
    </section>

    <!-- TAB 2: TUGAS TERBARU -->
    <section x-show="activeTab === 'tugas'" style="display: none;" aria-label="Daftar tugas siswa">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Daftar Tugas Kelas {{ $siswa->kelas }}</h2>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar" role="group" aria-label="Filter status tugas">
                <button
                    type="button"
                    @click="tugasFilter = 'semua'"
                    :class="tugasFilter === 'semua'
                        ? 'bg-[#166EE1] text-white shadow-2xs ring-1 ring-[#166EE1]'
                        : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                >
                    Semua ({{ $tugasTerbaru->count() }})
                </button>
                <button
                    type="button"
                    @click="tugasFilter = 'belum'"
                    :class="tugasFilter === 'belum'
                        ? 'bg-[#166EE1] text-white shadow-2xs ring-1 ring-[#166EE1]'
                        : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                >
                    Belum Dikumpulkan ({{ $unreadTugasCount ?? 0 }})
                </button>
                <button
                    type="button"
                    @click="tugasFilter = 'selesai'"
                    :class="tugasFilter === 'selesai'
                        ? 'bg-[#166EE1] text-white shadow-2xs ring-1 ring-[#166EE1]'
                        : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                >
                    Sudah Dikumpulkan
                </button>
            </div>
        </div>

        <div class="space-y-3.5">
            @forelse($tugasTerbaru as $t)
                @php
                    $pengumpulan = $t->pengumpulanTugas->first();
                    $statusKategori = $pengumpulan ? 'selesai' : 'belum';
                    $leftBarClass = 'border-l-[#F82B60]';
                    $statusLabel = 'Belum Dikerjakan';
                    $statusBadge = 'bg-[#FFE8EE] text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096]';
                    $btnLabel = 'Kerjakan Tugas';
                    $btnStyle = 'bg-[#166EE1] text-white hover:bg-[#0D52AC] shadow-2xs';

                    if ($pengumpulan) {
                        if ($pengumpulan->status === 'dinilai') {
                            $leftBarClass = 'border-l-[#20C933]';
                            $statusLabel = 'Dinilai ' . $pengumpulan->nilai . '/100';
                            $statusBadge = 'bg-[#E4FBE7] text-[#0B7B3E] ring-1 ring-inset ring-[#0B7B3E]/20 dark:bg-[#20C933]/15 dark:text-[#4AE05C]';
                            $btnLabel = 'Lihat Nilai';
                            $btnStyle = 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                        } else {
                            $leftBarClass = 'border-l-[#FCB400]';
                            $statusLabel = 'Menunggu Penilaian';
                            $statusBadge = 'bg-[#FFF6D6] text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057]';
                            $btnLabel = 'Lihat Pengumpulan';
                            $btnStyle = 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                        }
                    }

                    $tenggatCarbon = \Carbon\Carbon::parse($t->tenggat_waktu);
                    $isOverdue = !$pengumpulan && $tenggatCarbon->lt($nowWib);
                    $tGuruNama = $t->guru->nama_lengkap ?? '-';
                    $tGuruInit = collect(explode(' ', trim($tGuruNama)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
                @endphp
                <div
                    x-show="tugasFilter === 'semua' || tugasFilter === '{{ $statusKategori }}'"
                    class="flex flex-col gap-4 rounded-xl border border-gray-200/90 border-l-4 {{ $leftBarClass }} bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] transition-colors duration-150 hover:border-[#166EE1]/40 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-md bg-[#EBF3FF] px-2.5 py-0.5 text-xs font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/15 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                                {{ $t->mataPelajaran->nama ?? '-' }}
                            </span>
                            <span class="inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold tabular-nums {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-base font-bold text-gray-900 dark:text-white">
                            {{ $t->judul }}
                        </h3>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[9px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                    {{ $tGuruInit }}
                                </span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $tGuruNama }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 font-semibold tabular-nums {{ $isOverdue ? 'text-[#C91B4B] dark:text-[#FF7096]' : 'text-gray-700 dark:text-gray-300' }} dark:bg-gray-800">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                                <span>Tenggat {{ $tenggatCarbon->translatedFormat('d M Y, H:i') }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <a
                            href="{{ route('siswa.tugas.show', $t) }}"
                            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto {{ $btnStyle }}"
                        >
                            {{ $btnLabel }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Belum ada tugas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada tugas yang diberikan guru untuk kelas {{ $siswa->kelas }} saat ini.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- TAB 3: ABSENSI SAYA -->
    <section x-show="activeTab === 'absensi'" style="display: none;" aria-label="Absensi dan rekap kehadiran siswa">
        <!-- 3-Stage Cutoff Rules Summary Cards -->
        <div class="mb-6 rounded-xl border border-gray-200/90 bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] sm:p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Ketentuan Jam Kehadiran Sekolah</h2>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200/80 border-l-4 border-l-emerald-500 bg-[#E4FBE7]/45 p-3 dark:border-gray-700 dark:bg-emerald-500/10">
                    <dt class="font-semibold text-[#0B7B3E] dark:text-[#4AE05C]">Tepat waktu (Hadir)</dt>
                    <dd class="mt-1 font-bold tabular-nums text-gray-900 dark:text-white">Sampai pukul {{ $batasHadirStr }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200/80 border-l-4 border-l-amber-500 bg-[#FFF6D6]/55 p-3 dark:border-gray-700 dark:bg-amber-500/10">
                    <dt class="font-semibold text-[#9C6200] dark:text-[#FFD057]">Toleransi terlambat</dt>
                    <dd class="mt-1 font-bold tabular-nums text-gray-900 dark:text-white">{{ $batasHadirStr }} &ndash; {{ $batasAlphaStr }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200/80 border-l-4 border-l-rose-500 bg-[#FFE8EE]/55 p-3 dark:border-gray-700 dark:bg-rose-500/10">
                    <dt class="font-semibold text-[#C91B4B] dark:text-[#FF7096]">Batas terkunci (Alfa)</dt>
                    <dd class="mt-1 font-bold tabular-nums text-gray-900 dark:text-white">Lewat pukul {{ $batasAlphaStr }}</dd>
                </div>
            </dl>
        </div>

        @if(in_array($metode ?? 'RFID', ['Zonasi', 'Hybrid']))
            @include('components.gps-absensi', [
                'pengaturan' => $pengaturan ?? null,
                'allowedLocations' => $allowedLocations ?? collect(),
                'isLibur' => $isLibur ?? false,
                'absensiStatus' => $absensiStatus ?? null
            ])
        @else
            <div class="mb-6 rounded-xl border border-gray-200/90 border-l-4 border-l-[#166EE1] bg-white p-5 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Absensi Kartu RFID (Tap Kiosk)</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Sekolah saat ini menggunakan mesin tap kartu RFID untuk pencatatan kehadiran siswa.
                    @if($absensiStatus && ($absensiStatus['sudah_check_in'] ?? false))
                        Status hari ini: <strong class="text-gray-900 dark:text-white">{{ $absensiStatus['status'] }}</strong> (Masuk <span class="tabular-nums">{{ $absensiStatus['jam_masuk'] ?? '-' }}</span>@if($absensiStatus['jam_keluar'] ?? null), Keluar <span class="tabular-nums">{{ $absensiStatus['jam_keluar'] }}</span>@endif).
                    @endif
                </p>
            </div>
        @endif

        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Rekap Kehadiran per Mata Pelajaran</h2>
            <span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ count($rekapAbsensi) }} kategori</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($rekapAbsensi as $mapel => $rekap)
                @php
                    $totalKehadiran = ($rekap['Hadir'] ?? 0) + ($rekap['Terlambat'] ?? 0);
                    $persentase = ($rekap['Total'] ?? 0) > 0 ? round(($totalKehadiran / $rekap['Total']) * 100) : 0;
                    $progressBarClass = $persentase >= 85 ? 'bg-emerald-500' : ($persentase >= 70 ? 'bg-amber-500' : 'bg-rose-500');
                    $persentaseChip = $persentase >= 85
                        ? 'bg-[#E4FBE7] text-[#0B7B3E] dark:bg-[#20C933]/15 dark:text-[#4AE05C]'
                        : ($persentase >= 70
                            ? 'bg-[#FFF6D6] text-[#9C6200] dark:bg-[#FCB400]/15 dark:text-[#FFD057]'
                            : 'bg-[#FFE8EE] text-[#C91B4B] dark:bg-[#F82B60]/15 dark:text-[#FF7096]');
                @endphp
                <div x-data="{ open: false }" class="flex flex-col overflow-hidden rounded-xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        class="flex w-full items-center justify-between gap-3 border-b border-gray-200 px-4 py-3.5 text-left transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#166EE1] sm:px-5 dark:border-gray-800 dark:hover:bg-gray-800/40"
                    >
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $rekap['title'] ?? $mapel }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="inline-flex rounded px-1.5 py-0.5 text-[11px] font-bold tabular-nums {{ $persentaseChip }}">
                                    {{ $persentase }}%
                                </span>
                                <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $totalKehadiran }}/{{ $rekap['Total'] }} pertemuan
                                </span>
                            </div>
                        </div>
                        <svg
                            class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-150"
                            :class="open ? 'rotate-180' : ''"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="p-4 sm:p-5">
                        <dl class="grid grid-cols-5 gap-1.5 text-center">
                            <div class="rounded-lg bg-[#E4FBE7]/60 px-1 py-2 dark:bg-emerald-500/10">
                                <dt class="text-[11px] font-medium text-[#0B7B3E] dark:text-[#4AE05C]">Hadir</dt>
                                <dd class="mt-0.5 text-base font-bold tabular-nums text-[#0B7B3E] dark:text-[#4AE05C]">{{ $rekap['Hadir'] }}</dd>
                            </div>
                            <div class="rounded-lg bg-[#FFF6D6]/70 px-1 py-2 dark:bg-amber-500/10">
                                <dt class="text-[11px] font-medium text-[#9C6200] dark:text-[#FFD057]">Telat</dt>
                                <dd class="mt-0.5 text-base font-bold tabular-nums text-[#9C6200] dark:text-[#FFD057]">{{ $rekap['Terlambat'] }}</dd>
                            </div>
                            <div class="rounded-lg bg-[#EBF3FF]/80 px-1 py-2 dark:bg-sky-500/10">
                                <dt class="text-[11px] font-medium text-[#0D52AC] dark:text-[#66A8FF]">Izin</dt>
                                <dd class="mt-0.5 text-base font-bold tabular-nums text-[#0D52AC] dark:text-[#66A8FF]">{{ $rekap['Izin'] }}</dd>
                            </div>
                            <div class="rounded-lg bg-orange-50 px-1 py-2 dark:bg-orange-500/10">
                                <dt class="text-[11px] font-medium text-orange-700 dark:text-orange-400">Sakit</dt>
                                <dd class="mt-0.5 text-base font-bold tabular-nums text-orange-700 dark:text-orange-400">{{ $rekap['Sakit'] }}</dd>
                            </div>
                            <div class="rounded-lg bg-[#FFE8EE]/70 px-1 py-2 dark:bg-rose-500/10">
                                <dt class="text-[11px] font-medium text-[#C91B4B] dark:text-[#FF7096]">Alfa</dt>
                                <dd class="mt-0.5 text-base font-bold tabular-nums text-[#C91B4B] dark:text-[#FF7096]">{{ $rekap['Alfa'] }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full {{ $progressBarClass }}" style="width: {{ $persentase }}%"></div>
                        </div>

                        <div x-show="open" class="mt-4 border-t border-gray-200 pt-3.5 dark:border-gray-800" style="display: none;">
                            <h4 class="mb-2 text-xs font-bold text-gray-700 dark:text-gray-300">Riwayat Kehadiran</h4>
                            <div class="max-h-56 divide-y divide-gray-100 overflow-y-auto pr-1 custom-scrollbar dark:divide-gray-800">
                                @forelse(collect($rekap['Details'])->sortByDesc('tanggal') as $detail)
                                    <div class="flex items-start justify-between gap-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold tabular-nums text-gray-900 dark:text-white">
                                                {{ \Carbon\Carbon::parse($detail->tanggal)->translatedFormat('d M Y') }}
                                            </p>
                                            <p class="mt-0.5 text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                                                @if($detail->jadwal)
                                                    Jadwal {{ substr($detail->jadwal->jam_mulai ?? '00:00', 0, 5) }}-{{ substr($detail->jadwal->jam_selesai ?? '00:00', 0, 5) }}
                                                @else
                                                    Absensi Harian
                                                @endif
                                                @if($detail->jam_masuk)
                                                    (Masuk {{ \Carbon\Carbon::parse($detail->jam_masuk)->format('H:i') }})
                                                @endif
                                            </p>
                                            @if($detail->keterangan && $detail->keterangan !== 'Otomatis mengikuti absen pertama')
                                                <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $detail->keterangan }}</p>
                                            @endif
                                        </div>
                                        @php
                                            $dStatus = $detail->status === 'Alpha' ? 'Alfa' : $detail->status;
                                            $dClass = match ($dStatus) {
                                                'Hadir' => 'bg-[#E4FBE7] text-[#0B7B3E] dark:bg-[#20C933]/15 dark:text-[#4AE05C]',
                                                'Terlambat' => 'bg-[#FFF6D6] text-[#9C6200] dark:bg-[#FCB400]/15 dark:text-[#FFD057]',
                                                'Izin' => 'bg-[#EBF3FF] text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]',
                                                'Sakit' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400',
                                                default => 'bg-[#FFE8EE] text-[#C91B4B] dark:bg-[#F82B60]/15 dark:text-[#FF7096]',
                                            };
                                        @endphp
                                        <span class="shrink-0 rounded px-2 py-0.5 text-[11px] font-semibold {{ $dClass }}">
                                            {{ $dStatus }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-3 text-center text-xs text-gray-500 dark:text-gray-400">Belum ada riwayat kehadiran.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Belum ada rekap absensi</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catatan kehadiran per mata pelajaran akan tampil di sini setelah sesi belajar dimulai.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Fixed Mobile Bottom Navigation Bar (Airtable Tactile Active Pill) -->
    <nav
        aria-label="Navigasi cepat mobile"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 px-2 py-1 backdrop-blur-sm md:hidden dark:border-gray-800 dark:bg-gray-900/95"
    >
        <div class="grid grid-cols-4 gap-1">
            <button
                type="button"
                @click="switchTab('jadwal'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'jadwal' ? 'bg-[#EBF3FF] text-[#166EE1] dark:bg-[#166EE1]/20 dark:text-[#4EA8FF]' : 'text-gray-500 dark:text-gray-400'"
                class="flex min-h-[48px] flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#166EE1]"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18" />
                </svg>
                <span>Jadwal</span>
            </button>

            <button
                type="button"
                @click="switchTab('tugas'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'tugas' ? 'bg-[#EBF3FF] text-[#166EE1] dark:bg-[#166EE1]/20 dark:text-[#4EA8FF]' : 'text-gray-500 dark:text-gray-400'"
                class="relative flex min-h-[48px] flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#166EE1]"
            >
                <div class="relative">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    @if(isset($unreadTugasCount) && $unreadTugasCount > 0)
                        <span class="absolute -top-1.5 -right-2.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#F82B60] px-1 text-[10px] font-bold tabular-nums text-white">
                            {{ $unreadTugasCount }}
                        </span>
                    @endif
                </div>
                <span>Tugas</span>
            </button>

            <button
                type="button"
                @click="switchTab('absensi'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'absensi' ? 'bg-[#EBF3FF] text-[#166EE1] dark:bg-[#166EE1]/20 dark:text-[#4EA8FF]' : 'text-gray-500 dark:text-gray-400'"
                class="flex min-h-[48px] flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#166EE1]"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Absensi</span>
            </button>

            <a
                href="{{ url('/kalender') }}"
                class="flex min-h-[48px] flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold text-gray-500 transition-colors duration-150 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#166EE1] dark:text-gray-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Kalender</span>
            </a>
        </div>
    </nav>
</div>
@endsection
