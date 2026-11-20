@extends('layouts.app')

@section('content')
@php
    $guruNama = $tugas->guru->nama_lengkap ?? '-';
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
                    <li class="truncate">{{ $tugas->mataPelajaran->nama ?? 'Tugas' }}</li>
                    <li aria-hidden="true">/</li>
                    <li class="truncate font-semibold text-gray-900 dark:text-white">{{ $tugas->judul }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex rounded-md bg-[#EBF3FF] px-2.5 py-1 text-xs font-semibold text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                        Penugasan
                    </span>
                    <h1 class="break-words text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                        {{ $tugas->judul }}
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

    @if(session('success'))
        <div role="status" class="mb-6 flex items-center gap-3 rounded-xl border border-[#0B7B3E]/25 bg-[#E4FBE7] p-4 text-sm font-semibold text-[#0B7B3E] dark:border-[#20C933]/30 dark:bg-[#20C933]/15 dark:text-[#4AE05C]">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-[#C91B4B]/25 bg-[#FFE8EE] p-4 text-sm text-[#C91B4B] dark:border-[#F82B60]/30 dark:bg-[#F82B60]/15 dark:text-[#FF7096]">
            <p class="font-bold">Pengumpulan belum dapat diproses:</p>
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
            <section aria-label="Informasi tugas" class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-[#166EE1] bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Mata pelajaran</dt>
                    <dd class="mt-1.5">
                        <span class="inline-flex rounded-md bg-[#EBF3FF] px-2 py-0.5 text-xs font-bold text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                            {{ $tugas->mataPelajaran->nama ?? '-' }}
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

                <div class="rounded-xl border border-gray-200/90 border-l-4 border-l-rose-500 bg-white p-4 shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Batas pengumpulan</dt>
                    <dd class="mt-1.5 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                        <time datetime="{{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->toIso8601String() }}">
                            {{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->translatedFormat('d M Y, H:i') }}
                        </time>
                    </dd>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_3px_rgba(16,24,40,0.06)] sm:p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-3 text-base font-bold text-gray-900 dark:text-white">Instruksi Pengerjaan</h2>
                <div class="max-w-[70ch] rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 ring-1 ring-inset ring-gray-200/70 dark:bg-gray-800/50 dark:text-gray-300 dark:ring-gray-700">
                    <div class="whitespace-pre-wrap">
                        @php
                            $deskripsi = htmlspecialchars($tugas->deskripsi);
                            $deskripsi = preg_replace(
                                '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
                                '<div class="relative mt-4 mb-4 w-full max-w-2xl overflow-hidden rounded-xl border border-gray-200 pb-[56.25%] dark:border-gray-700"><iframe class="absolute top-0 left-0 h-full w-full" src="https://www.youtube.com/embed/$1" title="Video pendukung tugas" frameborder="0" allowfullscreen></iframe></div>',
                                $deskripsi
                            );
                        @endphp
                        {!! nl2br($deskripsi) !!}
                    </div>
                </div>

                @if($tugas->file_path)
                    <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <h3 class="mb-3 text-sm font-bold text-gray-900 dark:text-white">Lampiran dari Guru</h3>
                        <a
                            href="{{ asset('storage/' . $tugas->file_path) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-[#166EE1] transition-colors duration-150 hover:border-[#166EE1] hover:bg-[#EBF3FF] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-[#4EA8FF]"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
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
            <section class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-[0_1px_3px_rgba(16,24,40,0.06)] dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50/70 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/40">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Status &amp; Pengumpulan</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($pengumpulan && $pengumpulan->status === 'dinilai')
                            <span class="inline-flex rounded-md bg-[#E4FBE7] px-2.5 py-1 text-xs font-semibold text-[#0B7B3E] ring-1 ring-inset ring-[#0B7B3E]/20 dark:bg-[#20C933]/15 dark:text-[#4AE05C]">
                                Sudah Dinilai
                            </span>
                        @elseif($pengumpulan)
                            <span class="inline-flex rounded-md bg-[#FFF6D6] px-2.5 py-1 text-xs font-semibold text-[#9C6200] ring-1 ring-inset ring-[#9C6200]/20 dark:bg-[#FCB400]/15 dark:text-[#FFD057]">
                                Menunggu Penilaian
                            </span>
                        @else
                            <span class="inline-flex rounded-md bg-[#FFE8EE] px-2.5 py-1 text-xs font-semibold text-[#C91B4B] ring-1 ring-inset ring-[#C91B4B]/20 dark:bg-[#F82B60]/15 dark:text-[#FF7096]">
                                Belum Dikerjakan
                            </span>
                        @endif

                        @if($pengumpulan)
                            @php
                                $isLate = \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->gt(\Carbon\Carbon::parse($tugas->tenggat_waktu));
                            @endphp
                            @if($isLate)
                                <span class="inline-flex rounded-md bg-[#FFE8EE] px-2.5 py-1 text-xs font-semibold text-[#C91B4B] dark:bg-[#F82B60]/15 dark:text-[#FF7096]">
                                    Melewati Tenggat
                                </span>
                            @else
                                <span class="inline-flex rounded-md bg-[#EBF3FF] px-2.5 py-1 text-xs font-semibold text-[#0D52AC] dark:bg-[#166EE1]/20 dark:text-[#66A8FF]">
                                    Tepat Waktu
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    @if($pengumpulan)
                        <div class="rounded-xl border border-gray-200/80 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Dikumpulkan pada</p>
                            <p class="mt-0.5 text-sm font-bold tabular-nums text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->translatedFormat('d M Y, H:i') }}
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($pengumpulan->file_path)
                                    <a
                                        href="{{ asset('storage/' . $pengumpulan->file_path) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-[#166EE1] shadow-2xs transition-colors duration-150 hover:bg-[#EBF3FF] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-700 dark:bg-gray-900 dark:text-[#4EA8FF]"
                                    >
                                        <span>Lihat Berkas Jawaban</span>
                                    </a>
                                @endif

                                @if($pengumpulan->link_url)
                                    <a
                                        href="{{ $pengumpulan->link_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-[#166EE1] shadow-2xs transition-colors duration-150 hover:bg-[#EBF3FF] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1] dark:border-gray-700 dark:bg-gray-900 dark:text-[#4EA8FF]"
                                    >
                                        <span>Buka Tautan Jawaban</span>
                                    </a>
                                @endif
                            </div>

                            @if($pengumpulan->status === 'dinilai')
                                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nilai Akhir</p>
                                    <p class="mt-1 text-3xl font-bold tabular-nums text-[#0B7B3E] dark:text-[#4AE05C]">
                                        {{ $pengumpulan->nilai }} <span class="text-base font-medium text-gray-500 dark:text-gray-400">/ 100</span>
                                    </p>

                                    @if($pengumpulan->catatan_guru)
                                        <div class="mt-3 rounded-xl bg-white p-3.5 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
                                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">Catatan Guru</p>
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
                                    <label for="file_jawaban" class="block text-sm font-bold text-gray-900 dark:text-white">
                                        Unggah Berkas Jawaban
                                        @if(!in_array('link_url', $tipePengumpulan))
                                            <span class="text-[#C91B4B]">*</span>
                                        @endif
                                    </label>
                                    <label
                                        for="file_jawaban"
                                        class="mt-2 flex min-h-[104px] cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center transition-colors duration-150 hover:border-[#166EE1] hover:bg-[#EBF3FF]/45 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-[#4EA8FF]"
                                    >
                                        <svg class="h-6 w-6 text-[#166EE1] dark:text-[#4EA8FF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200" x-text="fileName ? 'Berkas dipilih: ' + fileName : 'Pilih berkas PDF, Word, Gambar, atau ZIP'"></span>
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
                                    <label for="link_url" class="block text-sm font-bold text-gray-900 dark:text-white">
                                        Tautan URL Jawaban
                                        @if(!in_array('file', $tipePengumpulan))
                                            <span class="text-[#C91B4B]">*</span>
                                        @endif
                                    </label>
                                    <input
                                        id="link_url"
                                        type="url"
                                        name="link_url"
                                        value="{{ old('link_url', $pengumpulan->link_url ?? '') }}"
                                        {{ !in_array('file', $tipePengumpulan) ? 'required' : '' }}
                                        placeholder="https://drive.google.com/..."
                                        class="mt-2 block min-h-[44px] w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-base text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:outline-[#166EE1] sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        Pastikan akses tautan (misalnya Google Drive) diatur agar guru dapat membuka berkas.
                                    </p>
                                </div>
                            @endif

                            <button
                                type="submit"
                                class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#166EE1] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[#0D52AC] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#166EE1]"
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
