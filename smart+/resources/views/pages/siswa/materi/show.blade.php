@extends('layouts.app')

@section('content')
@php
    $formatLabel = match ($materi->tipe_materi) {
        'pdf' => 'Dokumen PDF',
        'youtube' => 'Video YouTube',
        default => 'Tautan Eksternal',
    };
@endphp

<div class="mx-auto max-w-screen-xl px-4 pt-4 pb-12 sm:px-6 md:pt-6">
    <!-- Header & Navigation -->
    <header class="mb-6 border-b border-gray-200 pb-5 dark:border-gray-800">
        <nav aria-label="Breadcrumb" class="mb-3">
            <ol class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <li>
                    <a href="{{ route('siswa.dashboard') }}" class="hover:text-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:hover:text-brand-400">
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
            <h1 class="break-words text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                {{ $materi->judul }}
            </h1>
            <a
                href="javascript:history.back()"
                class="inline-flex min-h-[40px] shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali</span>
            </a>
        </div>
    </header>

    <!-- Metadata Bar -->
    <section aria-label="Informasi materi" class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-200 md:grid-cols-4 md:divide-y-0 dark:divide-gray-800">
            <div class="p-4">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Mata pelajaran</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $materi->mataPelajaran->nama ?? '-' }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Guru pengampu</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $materi->guru->nama_lengkap ?? '-' }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Format materi</dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $formatLabel }}
                    </span>
                </dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal unggah</dt>
                <dd class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                    <time datetime="{{ \Carbon\Carbon::parse($materi->created_at)->toIso8601String() }}">
                        {{ \Carbon\Carbon::parse($materi->created_at)->translatedFormat('d M Y, H:i') }}
                    </time>
                </dd>
            </div>
        </dl>
    </section>

    <!-- Main Content Surface -->
    <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <h2 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Deskripsi Materi</h2>
            <div class="max-w-[70ch] rounded-lg bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                <p class="whitespace-pre-wrap">{{ $materi->deskripsi ?? 'Tidak ada deskripsi tambahan.' }}</p>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
            <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Konten Pembelajaran</h2>

            @if($materi->tipe_materi === 'youtube')
                @php
                    $videoId = '';
                    $url = $materi->file_path_atau_url;
                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
                        $videoId = $match[1];
                    }
                @endphp
                @if($videoId)
                    <div class="relative w-full max-w-4xl overflow-hidden rounded-xl border border-gray-200 pb-[56.25%] dark:border-gray-800">
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
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Buka Video di YouTube</span>
                    </a>
                @endif
            @elseif($materi->tipe_materi === 'pdf')
                <div class="mb-4 flex flex-col gap-3 rounded-lg bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:bg-gray-800/50">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Berkas Dokumen PDF</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pada perangkat mobile, gunakan tombol di samping untuk membuka atau mengunduh berkas secara penuh.</p>
                    </div>
                    <a
                        href="{{ asset('storage/' . $materi->file_path_atau_url) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
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
                    class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Buka Tautan Materi</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
