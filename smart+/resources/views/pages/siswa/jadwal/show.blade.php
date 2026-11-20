@extends('layouts.app')

@section('content')
@php
    $guruNama = $jadwal->guru->nama_lengkap ?? '-';
    $guruInit = collect(explode(' ', trim($guruNama)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
@endphp

<div class="mx-auto max-w-screen-xl px-4 pt-4 pb-12 sm:px-6 md:pt-6" x-data="{ activeTab: 'materi' }">
    <!-- Airtable Base Header Card -->
    <header class="mb-6 overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
        <div class="h-1.5 w-full bg-[#166EE1]"></div>
        <div class="p-5 sm:p-6">
            <nav aria-label="Breadcrumb" class="mb-3">
                <ol class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <li>
                        <a href="{{ route('siswa.dashboard') }}" class="hover:text-[#166EE1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:hover:text-[#4EA8FF]">
                            Jadwal Pelajaran
                        </a>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li class="truncate font-semibold text-gray-900 dark:text-white">{{ $jadwal->mataPelajaran->nama ?? 'Mata Pelajaran' }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex rounded-md bg-[#EBF3FF] px-2.5 py-1 text-xs font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                        Ruang Kelas
                    </span>
                    <h1 class="break-words text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                        {{ $jadwal->mataPelajaran->nama ?? '-' }}
                    </h1>
                </div>
                <a
                    href="{{ route('siswa.dashboard') }}"
                    class="inline-flex min-h-[40px] shrink-0 items-center justify-center gap-2 self-start rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Kembali ke Jadwal</span>
                </a>
            </div>
        </div>
    </header>

    <!-- 3 Airtable Metadata Field Cards -->
    <section aria-label="Informasi kelas" class="mb-6 grid grid-cols-1 gap-3.5 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-amber-500 bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Guru pengampu</span>
            </dt>
            <dd class="mt-2 flex items-center gap-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                    {{ $guruInit }}
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $guruNama }}</span>
            </dd>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#166EE1] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="h-3.5 w-3.5 text-[#166EE1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                <span>Hari dan waktu</span>
            </dt>
            <dd class="mt-2 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                {{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
            </dd>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#5D2BC2] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                <svg class="h-3.5 w-3.5 text-[#5D2BC2]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-11a6 6 0 1112 0c0 5.314-6 11-6 11z"/><circle cx="12" cy="10" r="2.25"/></svg>
                <span>Ruang / lokasi</span>
            </dt>
            <dd class="mt-2">
                <span class="inline-flex rounded-md bg-[#F1E9FF] px-2.5 py-0.5 text-xs font-bold text-[#5D2BC2] dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]">
                    {{ $jadwal->lokasi->nama ?? '-' }}
                </span>
            </dd>
        </div>
    </section>

    <!-- Airtable Sunken-Tray Tab Switcher -->
    <div class="mb-6">
        <nav aria-label="Tab mata pelajaran" class="inline-flex w-full overflow-x-auto rounded-xl bg-gray-100/90 p-1 ring-1 ring-inset ring-gray-200/80 no-scrollbar sm:w-auto dark:bg-gray-800/90 dark:ring-gray-700/80">
            <button
                type="button"
                @click="activeTab = 'materi'"
                :aria-selected="activeTab === 'materi'"
                :class="activeTab === 'materi'
                    ? 'bg-white text-[#166EE1] shadow-sm ring-1 ring-gray-200/70 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-gray-700'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[40px] flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:flex-initial sm:text-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>Materi Belajar</span>
                <span class="rounded-full bg-[#EBF3FF] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">{{ $materi->count() }}</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'tugas'"
                :aria-selected="activeTab === 'tugas'"
                :class="activeTab === 'tugas'
                    ? 'bg-white text-[#166EE1] shadow-sm ring-1 ring-gray-200/70 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-gray-700'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[40px] flex-1 shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:flex-initial sm:text-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Daftar Tugas</span>
                <span class="rounded-full bg-[#EBF3FF] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">{{ $tugas->count() }}</span>
            </button>
        </nav>
    </div>

    <!-- Materi Tab Content -->
    <section x-show="activeTab === 'materi'" aria-label="Daftar materi belajar" class="space-y-3.5">
        @forelse($materi as $m)
            @php
                $formatLabel = match ($m->tipe_materi) {
                    'pdf' => 'Dokumen PDF',
                    'youtube' => 'Video YouTube',
                    default => 'Tautan Eksternal',
                };
                $formatPill = match ($m->tipe_materi) {
                    'pdf' => 'bg-[#FFE8EE] text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096]',
                    'youtube' => 'bg-[#FFF6D6] text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057]',
                    default => 'bg-[#F1E9FF] text-[#5D2BC2] ring-1 ring-inset ring-[#5D2BC2]/20 dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]',
                };
                $formatLeftBar = match ($m->tipe_materi) {
                    'pdf' => 'border-l-[#F82B60]',
                    'youtube' => 'border-l-[#FCB400]',
                    default => 'border-l-[#5D2BC2]',
                };
            @endphp
            <div class="flex flex-col gap-4 rounded-xl border border-gray-200/90 border-l-4 {{ $formatLeftBar }} bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] transition-colors duration-150 hover:border-[#166EE1]/40 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        @if($m->tipe_materi === 'pdf')
                            <svg class="h-5 w-5 text-[#C91B4B] dark:text-[#FF7096]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        @elseif($m->tipe_materi === 'youtube')
                            <svg class="h-5 w-5 text-[#9C6200] dark:text-[#FFD057]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5 text-[#5D2BC2] dark:text-[#C5A3FF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $formatPill }}">
                                {{ $formatLabel }}
                            </span>
                            <time datetime="{{ \Carbon\Carbon::parse($m->created_at)->toIso8601String() }}" class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                Diunggah {{ \Carbon\Carbon::parse($m->created_at)->translatedFormat('d M Y, H:i') }}
                            </time>
                        </div>
                        <h2 class="mt-1.5 text-base font-bold text-gray-900 dark:text-white">{{ $m->judul }}</h2>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $m->deskripsi ?? 'Tidak ada deskripsi tambahan.' }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <a
                        href="{{ route('siswa.materi.show', $m) }}"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#166EE1] px-4 py-2 text-sm font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto"
                    >
                        Buka Materi
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Belum ada materi belajar</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Guru pengampu belum mengunggah materi untuk mata pelajaran ini.</p>
            </div>
        @endforelse
    </section>

    <!-- Tugas Tab Content -->
    <section x-show="activeTab === 'tugas'" style="display: none;" aria-label="Daftar tugas mata pelajaran" class="space-y-3.5">
        @forelse($tugas as $t)
            @php
                $pengumpulan = $t->pengumpulanTugas->first();
                $leftAccent = 'border-l-[#F82B60]';
                $statusLabel = 'Belum Dikerjakan';
                $statusBadge = 'bg-[#FFE8EE] text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096]';
                $btnLabel = 'Kerjakan Tugas';
                $btnClass = 'bg-[#166EE1] text-white hover:bg-[#0D52AC] shadow-2xs';

                if ($pengumpulan) {
                    if ($pengumpulan->status === 'dinilai') {
                        $leftAccent = 'border-l-[#20C933]';
                        $statusLabel = 'Dinilai ' . $pengumpulan->nilai . '/100';
                        $statusBadge = 'bg-[#E4FBE7] text-[#0B7B3E] ring-1 ring-inset ring-[#0B7B3E]/20 dark:bg-[#20C933]/15 dark:text-[#4AE05C]';
                        $btnLabel = 'Lihat Nilai';
                        $btnClass = 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                    } else {
                        $leftAccent = 'border-l-[#FCB400]';
                        $statusLabel = 'Sudah Dikumpulkan';
                        $statusBadge = 'bg-[#FFF6D6] text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057]';
                        $btnLabel = 'Lihat Pengumpulan';
                        $btnClass = 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                    }
                }
            @endphp
            <div class="flex flex-col gap-4 rounded-xl border border-gray-200/90 border-l-4 {{ $leftAccent }} bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] transition-colors duration-150 hover:border-[#166EE1]/40 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold tabular-nums {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Tenggat {{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y, H:i') }}
                        </span>
                    </div>
                    <h2 class="mt-2 text-base font-bold text-gray-900 dark:text-white">{{ $t->judul }}</h2>
                    <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $t->deskripsi }}</p>
                </div>
                <div class="shrink-0">
                    <a
                        href="{{ route('siswa.tugas.show', $t) }}"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto {{ $btnClass }}"
                    >
                        {{ $btnLabel }}
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-2xs dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Belum ada tugas</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada penugasan yang dibuat guru untuk mata pelajaran ini.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
