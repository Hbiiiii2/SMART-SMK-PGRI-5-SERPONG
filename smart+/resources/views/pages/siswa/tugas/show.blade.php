@extends('layouts.app')

@section('content')
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
                <li class="truncate">{{ $tugas->mataPelajaran->nama ?? 'Tugas' }}</li>
                <li aria-hidden="true">/</li>
                <li class="truncate font-semibold text-gray-900 dark:text-white">{{ $tugas->judul }}</li>
            </ol>
        </nav>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="break-words text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                {{ $tugas->judul }}
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

    @if(session('success'))
        <div role="status" class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-500/10 dark:text-emerald-300">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-800/50 dark:bg-rose-500/10 dark:text-rose-300">
            <p class="font-semibold">Pengumpulan belum dapat diproses:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 12-Column Responsive Workbench -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Left Column: Metadata, Instructions & Teacher Attachment -->
        <div class="space-y-6 lg:col-span-7">
            <section aria-label="Informasi tugas" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <dl class="grid grid-cols-1 divide-y divide-gray-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-gray-800">
                    <div class="p-4">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Mata pelajaran</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $tugas->mataPelajaran->nama ?? '-' }}</dd>
                    </div>
                    <div class="p-4">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Guru pengampu</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $tugas->guru->nama_lengkap ?? '-' }}</dd>
                    </div>
                    <div class="p-4">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Batas waktu pengumpulan</dt>
                        <dd class="mt-1 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                            <time datetime="{{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->toIso8601String() }}">
                                {{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->translatedFormat('d M Y, H:i') }}
                            </time>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Instruksi Pengerjaan</h2>
                <div class="max-w-[70ch] rounded-lg bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                    <div class="whitespace-pre-wrap">
                        @php
                            $deskripsi = htmlspecialchars($tugas->deskripsi);
                            $deskripsi = preg_replace(
                                '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
                                '<div class="relative mt-4 mb-4 w-full max-w-2xl overflow-hidden rounded-lg border border-gray-200 pb-[56.25%] dark:border-gray-700"><iframe class="absolute top-0 left-0 h-full w-full" src="https://www.youtube.com/embed/$1" title="Video pendukung tugas" frameborder="0" allowfullscreen></iframe></div>',
                                $deskripsi
                            );
                        @endphp
                        {!! nl2br($deskripsi) !!}
                    </div>
                </div>

                @if($tugas->file_path)
                    <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Lampiran dari Guru</h3>
                        <a
                            href="{{ asset('storage/' . $tugas->file_path) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh Lampiran Soal</span>
                        </a>
                    </div>
                @endif
            </section>
        </div>

        <!-- Right Column: Submission Status, Grading & Upload Form -->
        <div class="lg:col-span-5">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Status &amp; Pengumpulan</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($pengumpulan && $pengumpulan->status === 'dinilai')
                            <span class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                                Sudah Dinilai
                            </span>
                        @elseif($pengumpulan)
                            <span class="inline-flex rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400">
                                Menunggu Penilaian
                            </span>
                        @else
                            <span class="inline-flex rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400">
                                Belum Dikerjakan
                            </span>
                        @endif

                        @if($pengumpulan)
                            @php
                                $isLate = \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->gt(\Carbon\Carbon::parse($tugas->tenggat_waktu));
                            @endphp
                            @if($isLate)
                                <span class="inline-flex rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                    Melewati Tenggat
                                </span>
                            @else
                                <span class="inline-flex rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                                    Tepat Waktu
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    @if($pengumpulan)
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Dikumpulkan pada</p>
                            <p class="mt-0.5 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->translatedFormat('d M Y, H:i') }}
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($pengumpulan->file_path)
                                    <a
                                        href="{{ asset('storage/' . $pengumpulan->file_path) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    >
                                        <span>Lihat Berkas Jawaban</span>
                                    </a>
                                @endif

                                @if($pengumpulan->link_url)
                                    <a
                                        href="{{ $pengumpulan->link_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                    >
                                        <span>Buka Tautan Jawaban</span>
                                    </a>
                                @endif
                            </div>

                            @if($pengumpulan->status === 'dinilai')
                                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nilai Akhir</p>
                                    <p class="mt-1 text-3xl font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                                        {{ $pengumpulan->nilai }} <span class="text-base font-normal text-gray-500 dark:text-gray-400">/ 100</span>
                                    </p>

                                    @if($pengumpulan->catatan_guru)
                                        <div class="mt-3 rounded-lg bg-white p-3 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">Catatan Guru</p>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $pengumpulan->catatan_guru }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!$pengumpulan || $pengumpulan->status !== 'dinilai')
                        @php
                            $tipePengumpulan = explode(',', $tugas->tipe_pengumpulan);
                        @endphp
                        <form
                            action="{{ route('siswa.tugas.submit', $tugas) }}"
                            method="POST"
                            enctype="multipart/form-data"
                            class="space-y-5"
                            x-data="{ fileName: '' }"
                        >
                            @csrf

                            @if(in_array('file', $tipePengumpulan))
                                <div>
                                    <label for="file_jawaban" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                        Unggah Berkas Jawaban
                                        @if(!in_array('link_url', $tipePengumpulan))
                                            <span class="text-rose-600">*</span>
                                        @endif
                                    </label>
                                    <label
                                        for="file_jawaban"
                                        class="mt-2 flex min-h-[96px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center transition-colors duration-150 hover:border-brand-500 hover:bg-brand-50/40 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-brand-400"
                                    >
                                        <svg class="h-6 w-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-200" x-text="fileName ? 'Berkas dipilih: ' + fileName : 'Pilih berkas PDF, Word, Gambar, atau ZIP'"></span>
                                        <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ukuran maksimal 10 MB</span>
                                        <input
                                            id="file_jawaban"
                                            type="file"
                                            name="file_jawaban"
                                            {{ !in_array('link_url', $tipePengumpulan) ? 'required' : '' }}
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                                            @change="fileName = $event.target.files[0]?.name || ''"
                                            class="sr-only"
                                        >
                                    </label>
                                </div>
                            @endif

                            @if(in_array('link_url', $tipePengumpulan))
                                <div>
                                    <label for="link_url" class="block text-sm font-semibold text-gray-900 dark:text-white">
                                        Tautan URL Jawaban
                                        @if(!in_array('file', $tipePengumpulan))
                                            <span class="text-rose-600">*</span>
                                        @endif
                                    </label>
                                    <input
                                        id="link_url"
                                        type="url"
                                        name="link_url"
                                        value="{{ old('link_url', $pengumpulan->link_url ?? '') }}"
                                        {{ !in_array('file', $tipePengumpulan) ? 'required' : '' }}
                                        placeholder="https://drive.google.com/..."
                                        class="mt-2 block min-h-[44px] w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-base text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:outline-brand-600 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        Pastikan akses tautan (misalnya Google Drive) diatur agar guru dapat membuka berkas.
                                    </p>
                                </div>
                            @endif

                            <button
                                type="submit"
                                class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                            >
                                {{ $pengumpulan ? 'Perbarui Pengumpulan Tugas' : 'Kumpulkan Tugas' }}
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
