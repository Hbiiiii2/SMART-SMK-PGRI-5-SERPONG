@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-xl px-4 pt-4 pb-12 sm:px-6 md:pt-6" x-data="{ activeTab: 'materi' }">
    <!-- Breadcrumb & Subject Header -->
    <header class="mb-6 border-b border-gray-200 pb-5 dark:border-gray-800">
        <nav aria-label="Breadcrumb" class="mb-3">
            <ol class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <li>
                    <a href="{{ route('siswa.dashboard') }}" class="hover:text-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:hover:text-brand-400">
                        Jadwal Pelajaran
                    </a>
                </li>
                <li aria-hidden="true">/</li>
                <li class="truncate font-semibold text-gray-900 dark:text-white">{{ $jadwal->mataPelajaran->nama ?? 'Mata Pelajaran' }}</li>
            </ol>
        </nav>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="break-words text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                {{ $jadwal->mataPelajaran->nama ?? '-' }}
            </h1>
            <a
                href="{{ route('siswa.dashboard') }}"
                class="inline-flex min-h-[40px] shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali ke Jadwal</span>
            </a>
        </div>
    </header>

    <!-- 3-Cell Subject Metadata Bar -->
    <section aria-label="Informasi kelas" class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-1 divide-y divide-gray-200 md:grid-cols-3 md:divide-x md:divide-y-0 dark:divide-gray-800">
            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Guru pengampu</dt>
                <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $jadwal->guru->nama_lengkap ?? '-' }}</dd>
            </div>
            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Hari dan waktu</dt>
                <dd class="mt-1.5 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                    {{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                </dd>
            </div>
            <div class="p-4 sm:p-5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Ruang / lokasi</dt>
                <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $jadwal->lokasi->nama ?? '-' }}</dd>
            </div>
        </dl>
    </section>

    <!-- Segmented Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav aria-label="Tab mata pelajaran" class="-mb-px flex gap-4 overflow-x-auto no-scrollbar">
            <button
                type="button"
                @click="activeTab = 'materi'"
                :aria-selected="activeTab === 'materi'"
                :class="activeTab === 'materi'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-2 py-2.5 text-sm font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>Materi Belajar</span>
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $materi->count() }}</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'tugas'"
                :aria-selected="activeTab === 'tugas'"
                :class="activeTab === 'tugas'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-2 py-2.5 text-sm font-semibold whitespace-nowrap transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Daftar Tugas</span>
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $tugas->count() }}</span>
            </button>
        </nav>
    </div>

    <!-- Materi Tab Content -->
    <section x-show="activeTab === 'materi'" aria-label="Daftar materi belajar" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @forelse($materi as $m)
            @php
                $formatLabel = match ($m->tipe_materi) {
                    'pdf' => 'Dokumen PDF',
                    'youtube' => 'Video YouTube',
                    default => 'Tautan Eksternal',
                };
            @endphp
            <div class="flex flex-col gap-4 border-b border-gray-200 p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-gray-800">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        @if($m->tipe_materi === 'pdf')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        @elseif($m->tipe_materi === 'youtube')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $formatLabel }}
                            </span>
                            <time datetime="{{ \Carbon\Carbon::parse($m->created_at)->toIso8601String() }}" class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                Diunggah {{ \Carbon\Carbon::parse($m->created_at)->translatedFormat('d M Y, H:i') }}
                            </time>
                        </div>
                        <h2 class="mt-1.5 text-base font-semibold text-gray-900 dark:text-white">{{ $m->judul }}</h2>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $m->deskripsi ?? 'Tidak ada deskripsi tambahan.' }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <a
                        href="{{ route('siswa.materi.show', $m) }}"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto"
                    >
                        Buka Materi
                    </a>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Belum ada materi belajar</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Guru pengampu belum mengunggah materi untuk mata pelajaran ini.</p>
            </div>
        @endforelse
    </section>

    <!-- Tugas Tab Content -->
    <section x-show="activeTab === 'tugas'" style="display: none;" aria-label="Daftar tugas mata pelajaran" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @forelse($tugas as $t)
            @php
                $pengumpulan = $t->pengumpulanTugas->first();
                $statusLabel = 'Belum Dikerjakan';
                $statusBadge = 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400';
                $btnLabel = 'Kerjakan Tugas';
                $btnClass = 'bg-brand-600 text-white hover:bg-brand-700';

                if ($pengumpulan) {
                    if ($pengumpulan->status === 'dinilai') {
                        $statusLabel = 'Dinilai ' . $pengumpulan->nilai . '/100';
                        $statusBadge = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400';
                        $btnLabel = 'Lihat Nilai';
                        $btnClass = 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                    } else {
                        $statusLabel = 'Sudah Dikumpulkan';
                        $statusBadge = 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400';
                        $btnLabel = 'Lihat Pengumpulan';
                        $btnClass = 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
                    }
                }
            @endphp
            <div class="flex flex-col gap-4 border-b border-gray-200 p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:border-gray-800">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold tabular-nums {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                        <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                            Tenggat {{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y, H:i') }}
                        </span>
                    </div>
                    <h2 class="mt-1.5 text-base font-semibold text-gray-900 dark:text-white">{{ $t->judul }}</h2>
                    <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $t->deskripsi }}</p>
                </div>
                <div class="shrink-0">
                    <a
                        href="{{ route('siswa.tugas.show', $t) }}"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto {{ $btnClass }}"
                    >
                        {{ $btnLabel }}
                    </a>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Belum ada tugas</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada penugasan yang dibuat guru untuk mata pelajaran ini.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
