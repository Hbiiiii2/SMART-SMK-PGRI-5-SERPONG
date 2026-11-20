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
    $jamMasukStr = $jamMasukBase->format('H:i');
    $batasHadirStr = $jamMasukBase->copy()->addMinutes($tolTerlambat)->format('H:i');
    $batasAlphaStr = $jamMasukBase->copy()->addMinutes($tolAlpha)->format('H:i');

    $sudahCheckIn = (bool) ($absensiStatus['sudah_check_in'] ?? false);
    $sudahCheckOut = (bool) ($absensiStatus['sudah_check_out'] ?? false);
    $statusHariIniLabel = $absensiStatus['status'] ?? (($isLibur ?? false) ? 'Hari Libur' : 'Belum Absen');

    $statusBadgeClass = match ($statusHariIniLabel) {
        'Hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'Terlambat' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400',
        'Alfa', 'Alpha' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400',
        'Izin', 'Sakit' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-400',
        default => 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300',
    };

    $gpsAktif = in_array($metode ?? 'RFID', ['Zonasi', 'Hybrid']) && !($isLibur ?? false);
@endphp

<div
    class="mx-auto max-w-screen-xl px-4 pt-4 pb-24 sm:px-6 md:pt-6 md:pb-10"
    x-data="{
        activeTab: ['jadwal', 'tugas', 'absensi'].includes(new URLSearchParams(window.location.search).get('tab'))
            ? new URLSearchParams(window.location.search).get('tab')
            : 'jadwal',
        selectedDay: 'semua',
        tugasFilter: 'semua',
        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
    }"
>
    <!-- Workspace Identity Header -->
    <header class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-end sm:justify-between dark:border-gray-800">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                <time datetime="{{ $nowWib->toDateString() }}">{{ $tanggalHariIni }}</time>
            </p>
            <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                {{ $siswa->nama_lengkap }}
            </h1>
            <dl class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex items-center gap-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">Kelas</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $siswa->kelas }}</dd>
                </div>
                <div class="flex items-center gap-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">NISN</dt>
                    <dd class="font-medium tabular-nums text-gray-900 dark:text-white">{{ $siswa->nisn ?? '-' }}</dd>
                </div>
                <div class="flex items-center gap-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">Metode Absensi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $metode ?? 'RFID' }}</dd>
                </div>
            </dl>
        </div>

        @if($gpsAktif && !$sudahCheckOut)
            <div class="shrink-0">
                <button
                    type="button"
                    @click="switchTab('absensi')"
                    class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-11a6 6 0 1112 0c0 5.314-6 11-6 11z" />
                        <circle cx="12" cy="10" r="2.25" />
                    </svg>
                    <span>{{ $sudahCheckIn ? 'Lanjut Check-Out GPS' : 'Isi Absensi GPS' }}</span>
                </button>
            </div>
        @endif
    </header>

    <!-- 4-Cell Stat Ledger with Hairline Dividers -->
    <section aria-label="Ringkasan aktivitas siswa" class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-200 lg:grid-cols-4 lg:divide-y-0 dark:divide-gray-800">
            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Absensi hari ini</dt>
                <dd class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $statusBadgeClass }}">
                        {{ $statusHariIniLabel }}
                    </span>
                    @if($sudahCheckIn && !empty($absensiStatus['jam_masuk']))
                        <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                            {{ $absensiStatus['jam_masuk'] }}
                        </span>
                    @endif
                </dd>
                <p class="mt-1.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                    Batas tepat waktu {{ $batasHadirStr }}, batas akhir {{ $batasAlphaStr }}
                </p>
            </div>

            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Jadwal hari {{ strtolower($namaHariIni) }}</dt>
                <dd class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">
                    {{ $jadwalHariIni->count() }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">mapel</span>
                </dd>
                <p class="mt-1.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                    Total {{ $jadwals->count() }} sesi dalam seminggu
                </p>
            </div>

            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Tugas belum dikumpulkan</dt>
                <dd class="mt-2 text-2xl font-semibold tabular-nums {{ ($unreadTugasCount ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }}">
                    {{ $unreadTugasCount ?? 0 }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">tugas</span>
                </dd>
                <p class="mt-1.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                    Dari {{ $tugasTerbaru->count() }} tugas aktif terbaru
                </p>
            </div>

            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Persentase kehadiran</dt>
                <dd class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">
                    {{ $rataKehadiran }}%
                </dd>
                <p class="mt-1.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                    {{ $totalHadirAll + $totalTerlambatAll }} hadir dari {{ $totalSesiAll }} pertemuan
                </p>
            </div>
        </dl>
    </section>

    <!-- Desktop / Tablet Horizontal Segmented Tab Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav aria-label="Navigasi menu siswa" class="-mb-px flex gap-2 overflow-x-auto no-scrollbar">
            <button
                type="button"
                @click="switchTab('jadwal')"
                :aria-selected="activeTab === 'jadwal'"
                :class="activeTab === 'jadwal'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
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
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Tugas</span>
                @if(isset($unreadTugasCount) && $unreadTugasCount > 0)
                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1.5 text-xs font-semibold tabular-nums text-white">
                        {{ $unreadTugasCount }}
                    </span>
                @endif
            </button>

            <button
                type="button"
                @click="switchTab('absensi')"
                :aria-selected="activeTab === 'absensi'"
                :class="activeTab === 'absensi'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Absensi &amp; Rekap</span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: JADWAL PELAJARAN -->
    <section x-show="activeTab === 'jadwal'" aria-label="Jadwal pelajaran siswa">
        <!-- Today's Schedule Priority Timeline -->
        <div class="mb-8 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3.5 sm:px-6 dark:border-gray-800">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Jadwal Hari Ini ({{ $namaHariIni }})
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Pilih mata pelajaran untuk membuka materi ajar dan daftar tugas.
                    </p>
                </div>
                <span class="text-xs font-medium tabular-nums text-gray-500 dark:text-gray-400">
                    {{ $jadwalHariIni->count() }} sesi terjadwal
                </span>
            </div>

            @if($jadwalHariIni->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($jadwalHariIni as $jadwal)
                        @php
                            $mulai = substr($jadwal->jam_mulai, 0, 5);
                            $selesai = substr($jadwal->jam_selesai, 0, 5);
                            $isOngoing = $jamNowWib >= $jadwal->jam_mulai && $jamNowWib <= $jadwal->jam_selesai;
                            $isDone = $jamNowWib > $jadwal->jam_selesai;
                        @endphp
                        <a
                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                            class="group flex flex-col gap-3 px-4 py-4 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:hover:bg-gray-800/60"
                        >
                            <div class="flex min-w-0 items-start gap-4 sm:items-center">
                                <div class="w-24 shrink-0">
                                    <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $mulai }}–{{ $selesai }}</p>
                                    @if($isOngoing)
                                        <span class="mt-1 inline-flex rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            Berlangsung
                                        </span>
                                    @elseif($isDone)
                                        <span class="mt-1 inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="mt-1 inline-flex rounded bg-brand-50 px-1.5 py-0.5 text-[11px] font-medium text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                            Terjadwal
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-gray-900 group-hover:text-brand-600 sm:text-base dark:text-white dark:group-hover:text-brand-400">
                                        {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                    </h3>
                                    <p class="mt-0.5 truncate text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                                        {{ $jadwal->guru->nama_lengkap ?? '-' }} &ndash; Ruang {{ $jadwal->lokasi->nama ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand-600 dark:text-brand-400">
                                <span>Buka Kelas</span>
                                <svg class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-4 py-8 text-center sm:px-6">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada jadwal pelajaran untuk hari {{ $namaHariIni }}.</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gunakan daftar mingguan di bawah untuk meninjau materi dan tugas mata pelajaran lainnya.</p>
                </div>
            @endif
        </div>

        <!-- Weekly Schedule Filter & Full Ledger -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Jadwal Mingguan Lengkap</h2>
            @if($availableDays->isNotEmpty())
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar" role="group" aria-label="Filter hari jadwal">
                    <button
                        type="button"
                        @click="selectedDay = 'semua'"
                        :class="selectedDay === 'semua'
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                            : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                        class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                    >
                        Semua Hari
                    </button>
                    @foreach($availableDays as $dayLabel)
                        <button
                            type="button"
                            @click="selectedDay = '{{ $dayLabel }}'"
                            :class="selectedDay === '{{ $dayLabel }}'
                                ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                            class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                        >
                            {{ $dayLabel }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @forelse($groupedJadwals as $hari => $jadwalsHari)
            <div x-show="selectedDay === 'semua' || selectedDay === '{{ $hari }}'" class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2.5 sm:px-6 dark:border-gray-800 dark:bg-gray-800/50">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $hari }}</h3>
                    <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ $jadwalsHari->count() }} mata pelajaran</span>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Mata pelajaran</th>
                                <th class="px-6 py-3">Guru pengampu</th>
                                <th class="px-6 py-3">Ruang</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($jadwalsHari->sortBy('jam_mulai') as $jadwal)
                                <tr class="transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="whitespace-nowrap px-6 py-3.5 font-medium tabular-nums text-gray-900 dark:text-white">
                                        {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                                    </td>
                                    <td class="px-6 py-3.5 font-semibold text-gray-900 dark:text-white">
                                        {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-600 dark:text-gray-300">
                                        {{ $jadwal->guru->nama_lengkap ?? '-' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-600 dark:text-gray-300">
                                        {{ $jadwal->lokasi->nama ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3.5 text-right">
                                        <a
                                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:border-brand-500 hover:text-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        >
                                            Detail Materi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Stack View -->
                <div class="divide-y divide-gray-200 md:hidden dark:divide-gray-800">
                    @foreach($jadwalsHari->sortBy('jam_mulai') as $jadwal)
                        <a
                            href="{{ route('siswa.jadwal.show', $jadwal) }}"
                            class="flex items-center justify-between gap-3 px-4 py-3.5 transition-colors duration-150 active:bg-gray-50 dark:active:bg-gray-800/60"
                        >
                            <div class="min-w-0">
                                <p class="text-xs font-semibold tabular-nums text-brand-600 dark:text-brand-400">
                                    {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                                </p>
                                <h4 class="mt-0.5 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $jadwal->mataPelajaran->nama ?? '-' }}
                                </h4>
                                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $jadwal->guru->nama_lengkap ?? '-' }} / {{ $jadwal->lokasi->nama ?? '-' }}
                                </p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Belum ada jadwal pelajaran</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jadwal pelajaran untuk kelas {{ $siswa->kelas }} pada tahun ajaran aktif belum tersedia.</p>
            </div>
        @endforelse
    </section>

    <!-- TAB 2: TUGAS TERBARU -->
    <section x-show="activeTab === 'tugas'" style="display: none;" aria-label="Daftar tugas siswa">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Tugas Kelas {{ $siswa->kelas }}</h2>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar" role="group" aria-label="Filter status tugas">
                <button
                    type="button"
                    @click="tugasFilter = 'semua'"
                    :class="tugasFilter === 'semua'
                        ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                        : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >
                    Semua ({{ $tugasTerbaru->count() }})
                </button>
                <button
                    type="button"
                    @click="tugasFilter = 'belum'"
                    :class="tugasFilter === 'belum'
                        ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                        : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >
                    Belum Dikumpulkan ({{ $unreadTugasCount ?? 0 }})
                </button>
                <button
                    type="button"
                    @click="tugasFilter = 'selesai'"
                    :class="tugasFilter === 'selesai'
                        ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                        : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700'"
                    class="min-h-[36px] shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >
                    Sudah Dikumpulkan
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            @forelse($tugasTerbaru as $t)
                @php
                    $pengumpulan = $t->pengumpulanTugas->first();
                    $statusKategori = $pengumpulan ? 'selesai' : 'belum';
                    $statusLabel = 'Belum Dikerjakan';
                    $statusBadge = 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400';
                    $btnLabel = 'Kerjakan Tugas';
                    $btnStyle = 'bg-brand-600 text-white hover:bg-brand-700';

                    if ($pengumpulan) {
                        if ($pengumpulan->status === 'dinilai') {
                            $statusLabel = 'Dinilai ' . $pengumpulan->nilai . '/100';
                            $statusBadge = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400';
                            $btnLabel = 'Lihat Nilai';
                            $btnStyle = 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                        } else {
                            $statusLabel = 'Menunggu Penilaian';
                            $statusBadge = 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400';
                            $btnLabel = 'Lihat Pengumpulan';
                            $btnStyle = 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                        }
                    }

                    $tenggatCarbon = \Carbon\Carbon::parse($t->tenggat_waktu);
                    $isOverdue = !$pengumpulan && $tenggatCarbon->lt($nowWib);
                @endphp
                <div
                    x-show="tugasFilter === 'semua' || tugasFilter === '{{ $statusKategori }}'"
                    class="flex flex-col gap-4 border-b border-gray-200 px-4 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-800"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $t->mataPelajaran->nama ?? '-' }}
                            </span>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold tabular-nums {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $t->judul }}
                        </h3>
                        <dl class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <dt>Guru:</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $t->guru->nama_lengkap ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center gap-1">
                                <dt>Tenggat:</dt>
                                <dd class="font-medium tabular-nums {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $tenggatCarbon->translatedFormat('d M Y, H:i') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="shrink-0">
                        <a
                            href="{{ route('siswa.tugas.show', $t) }}"
                            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto {{ $btnStyle }}"
                        >
                            {{ $btnLabel }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Belum ada tugas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada tugas yang diberikan guru untuk kelas {{ $siswa->kelas }} saat ini.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- TAB 3: ABSENSI SAYA -->
    <section x-show="activeTab === 'absensi'" style="display: none;" aria-label="Absensi dan rekap kehadiran siswa">
        <!-- 3-Stage Cutoff Rules Summary -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Ketentuan Jam Kehadiran Sekolah</h2>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-gray-200 dark:bg-gray-800/70 dark:ring-gray-700">
                    <dt class="font-medium text-emerald-700 dark:text-emerald-400">Tepat waktu (Hadir)</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-gray-900 dark:text-white">Sampai pukul {{ $batasHadirStr }}</dd>
                </div>
                <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-gray-200 dark:bg-gray-800/70 dark:ring-gray-700">
                    <dt class="font-medium text-amber-700 dark:text-amber-400">Toleransi terlambat</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-gray-900 dark:text-white">{{ $batasHadirStr }} &ndash; {{ $batasAlphaStr }}</dd>
                </div>
                <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-gray-200 dark:bg-gray-800/70 dark:ring-gray-700">
                    <dt class="font-medium text-rose-700 dark:text-rose-400">Batas terkunci (Alfa)</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-gray-900 dark:text-white">Lewat pukul {{ $batasAlphaStr }}</dd>
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
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Absensi Kartu RFID (Tap Kiosk)</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Sekolah saat ini menggunakan mesin tap kartu RFID untuk pencatatan kehadiran siswa.
                    @if($absensiStatus && ($absensiStatus['sudah_check_in'] ?? false))
                        Status hari ini: <strong class="text-gray-900 dark:text-white">{{ $absensiStatus['status'] }}</strong> (Masuk <span class="tabular-nums">{{ $absensiStatus['jam_masuk'] ?? '-' }}</span>@if($absensiStatus['jam_keluar'] ?? null), Keluar <span class="tabular-nums">{{ $absensiStatus['jam_keluar'] }}</span>@endif).
                    @endif
                </p>
            </div>
        @endif

        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Rekap Kehadiran per Mata Pelajaran</h2>
            <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ count($rekapAbsensi) }} kategori</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($rekapAbsensi as $mapel => $rekap)
                @php
                    $totalKehadiran = ($rekap['Hadir'] ?? 0) + ($rekap['Terlambat'] ?? 0);
                    $persentase = ($rekap['Total'] ?? 0) > 0 ? round(($totalKehadiran / $rekap['Total']) * 100) : 0;
                    $progressBarClass = $persentase >= 85 ? 'bg-emerald-600' : ($persentase >= 70 ? 'bg-amber-500' : 'bg-rose-600');
                    $progressTextClass = $persentase >= 85 ? 'text-emerald-700 dark:text-emerald-400' : ($persentase >= 70 ? 'text-amber-700 dark:text-amber-400' : 'text-rose-700 dark:text-rose-400');
                @endphp
                <div x-data="{ open: false }" class="flex flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        class="flex w-full items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 text-left transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600 dark:border-gray-800 dark:hover:bg-gray-800/40"
                    >
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $rekap['title'] ?? $mapel }}</h3>
                            <p class="mt-0.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                Kehadiran <span class="font-semibold {{ $progressTextClass }}">{{ $persentase }}%</span> ({{ $totalKehadiran }}/{{ $rekap['Total'] }})
                            </p>
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

                    <div class="p-5">
                        <dl class="grid grid-cols-5 divide-x divide-gray-200 text-center dark:divide-gray-800">
                            <div class="px-1">
                                <dt class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Hadir</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ $rekap['Hadir'] }}</dd>
                            </div>
                            <div class="px-1">
                                <dt class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Telat</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums text-amber-700 dark:text-amber-400">{{ $rekap['Terlambat'] }}</dd>
                            </div>
                            <div class="px-1">
                                <dt class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Izin</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums text-sky-700 dark:text-sky-400">{{ $rekap['Izin'] }}</dd>
                            </div>
                            <div class="px-1">
                                <dt class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Sakit</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums text-orange-700 dark:text-orange-400">{{ $rekap['Sakit'] }}</dd>
                            </div>
                            <div class="px-1">
                                <dt class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Alfa</dt>
                                <dd class="mt-1 text-base font-semibold tabular-nums text-rose-700 dark:text-rose-400">{{ $rekap['Alfa'] }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full {{ $progressBarClass }}" style="width: {{ $persentase }}%"></div>
                            </div>
                        </div>

                        <div x-show="open" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800" style="display: none;">
                            <h4 class="mb-2.5 text-xs font-semibold text-gray-700 dark:text-gray-300">Riwayat Kehadiran</h4>
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
                                                'Hadir' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
                                                'Terlambat' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
                                                'Izin' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
                                                'Sakit' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
                                                default => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
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
                <div class="col-span-full rounded-xl border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Belum ada rekap absensi</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catatan kehadiran per mata pelajaran akan tampil di sini setelah sesi belajar dimulai.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Fixed Mobile Bottom Navigation Bar -->
    <nav
        aria-label="Navigasi cepat mobile"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur-sm md:hidden dark:border-gray-800 dark:bg-gray-900/95"
    >
        <div class="grid h-16 grid-cols-4">
            <button
                type="button"
                @click="switchTab('jadwal'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'jadwal' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400'"
                class="flex min-h-[48px] flex-col items-center justify-center gap-1 text-[11px] font-semibold focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18" />
                </svg>
                <span>Jadwal</span>
            </button>

            <button
                type="button"
                @click="switchTab('tugas'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'tugas' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400'"
                class="relative flex min-h-[48px] flex-col items-center justify-center gap-1 text-[11px] font-semibold focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600"
            >
                <div class="relative">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    @if(isset($unreadTugasCount) && $unreadTugasCount > 0)
                        <span class="absolute -top-1.5 -right-2.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold tabular-nums text-white">
                            {{ $unreadTugasCount }}
                        </span>
                    @endif
                </div>
                <span>Tugas</span>
            </button>

            <button
                type="button"
                @click="switchTab('absensi'); window.scrollTo({ top: 0, behavior: 'smooth' })"
                :class="activeTab === 'absensi' ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400'"
                class="flex min-h-[48px] flex-col items-center justify-center gap-1 text-[11px] font-semibold focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Absensi</span>
            </button>

            <a
                href="{{ url('/kalender') }}"
                class="flex min-h-[48px] flex-col items-center justify-center gap-1 text-[11px] font-semibold text-gray-500 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600 dark:text-gray-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Kalender</span>
            </a>
        </div>
    </nav>
</div>
@endsection
