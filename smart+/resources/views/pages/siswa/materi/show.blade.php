@extends('layouts.app')

@section('content')
@php
    $formatLabel = match ($materi->tipe_materi) {
        'pdf' => 'Dokumen PDF',
        'youtube' => 'Video YouTube',
        default => 'Tautan Eksternal',
    };
    $formatPill = match ($materi->tipe_materi) {
        'pdf' => 'bg-[#FFE8EE] text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096]',
        'youtube' => 'bg-[#FFF6D6] text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057]',
        default => 'bg-[#F1E9FF] text-[#5D2BC2] ring-1 ring-inset ring-[#5D2BC2]/20 dark:bg-[#8B46FF]/15 dark:text-[#C5A3FF]',
    };
    $guruNama = $materi->guru->nama_lengkap ?? '-';
    $guruInit = collect(explode(' ', trim($guruNama)))->filter()->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'GR';
@endphp

<div class="mx-auto max-w-screen-xl px-4 pt-4 pb-12 sm:px-6 md:pt-6">
    <!-- Airtable Base Header Card -->
    <header class="mb-6 overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
        <div class="h-1.5 w-full bg-[#166EE1]"></div>
        <div class="p-5 sm:p-6">
            <nav aria-label="Breadcrumb" class="mb-3">
                <ol class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <li>
                        <a href="{{ route('siswa.dashboard') }}" class="hover:text-[#166EE1] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:hover:text-[#4EA8FF]">
                            Dashboard
                        </a>
                    </li>
                    <li aria-hidden="true">/</li>
                    <li class="truncate">{{ $materi->mataPelajaran->nama ?? 'Materi' }}</li>
                    <li aria-hidden="true">/</li>
                    <li class="truncate font-semibold text-gray-900 dark:text-white">{{ $materi->judul }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $formatPill }}">
                        {{ $formatLabel }}
                    </span>
                    <h1 class="break-words text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                        {{ $materi->judul }}
                    </h1>
                </div>
                <a
                    href="javascript:history.back()"
                    class="inline-flex min-h-[40px] shrink-0 items-center justify-center gap-2 self-start rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </header>

    <!-- 4 Airtable Field Metadata Cards -->
    <section aria-label="Informasi materi" class="mb-6 grid grid-cols-2 gap-3.5 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#166EE1] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Mata pelajaran</dt>
            <dd class="mt-1.5">
                <span class="inline-flex rounded-md bg-[#EBF3FF] px-2 py-0.5 text-xs font-bold text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                    {{ $materi->mataPelajaran->nama ?? '-' }}
                </span>
            </dd>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-amber-500 bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Guru pengampu</dt>
            <dd class="mt-1.5 flex items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                    {{ $guruInit }}
                </span>
                <span class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $guruNama }}</span>
            </dd>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#5D2BC2] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Format materi</dt>
            <dd class="mt-1.5">
                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $formatPill }}">
                    {{ $formatLabel }}
                </span>
            </dd>
        </div>

        <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-emerald-500 bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal unggah</dt>
            <dd class="mt-1.5 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                <time datetime="{{ \Carbon\Carbon::parse($materi->created_at)->toIso8601String() }}">
                    {{ \Carbon\Carbon::parse($materi->created_at)->translatedFormat('d M Y, H:i') }}
                </time>
            </dd>
        </div>
    </section>

    <!-- Main Content Card -->
    <div class="space-y-6 rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_3px_rgba(16,24,40,0.06)] sm:p-6 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <h2 class="mb-2 text-sm font-bold text-gray-900 dark:text-white">Deskripsi Materi</h2>
            <div class="max-w-[70ch] rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 ring-1 ring-inset ring-gray-200/70 dark:bg-gray-800/50 dark:text-gray-300 dark:ring-gray-700">
                <p class="whitespace-pre-wrap">{{ $materi->deskripsi ?? 'Tidak ada deskripsi tambahan.' }}</p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
            <h2 class="mb-4 text-sm font-bold text-gray-900 dark:text-white">Konten Pembelajaran</h2>

            @if($materi->tipe_materi === 'youtube')
                @php
                    $videoId = '';
                    $url = $materi->file_path_atau_url;
                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
                        $videoId = $match[1];
                    }
                @endphp
                @if($videoId)
                    <div class="relative w-full max-w-4xl overflow-hidden rounded-xl border border-gray-200 pb-[56.25%] shadow-sm dark:border-gray-800">
                        <iframe
                            class="absolute top-0 left-0 h-full w-full"
                            src="https://www.youtube.com/embed/{{ $videoId }}"
                            title="{{ $materi->judul }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>
                @else
                    <a
                        href="{{ $materi->file_path_atau_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#166EE1] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Buka Video di YouTube</span>
                    </a>
                @endif
            @elseif($materi->tipe_materi === 'pdf')
                <div class="mb-4 flex flex-col gap-3 rounded-xl border border-gray-200/80 bg-[#EBF3FF]/50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700 dark:bg-[#166EE1]/10">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Berkas Dokumen PDF</p>
                        <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Pada perangkat mobile, gunakan tombol di samping untuk membuka atau mengunduh berkas secara penuh.</p>
                    </div>
                    <a
                        href="{{ asset('storage/' . $materi->file_path_atau_url) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-xl bg-[#166EE1] px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Buka atau Unduh PDF</span>
                    </a>
                </div>
                <div class="hidden h-[700px] w-full max-w-5xl overflow-hidden rounded-xl border border-gray-200 md:block dark:border-gray-800">
                    <iframe src="{{ asset('storage/' . $materi->file_path_atau_url) }}" class="h-full w-full" title="{{ $materi->judul }}" frameborder="0"></iframe>
                </div>
            @else
                <a
                    href="{{ $materi->file_path_atau_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#166EE1] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Buka Tautan Materi</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
