@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Siswa: {{ $siswa->nama_lengkap }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lengkapi atau perbarui data induk sekolah &amp; Dapodik peserta didik.</p>
        </div>
        <a href="{{ route('admin.siswa.show', $siswa) }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
            Lihat Profil Lengkap
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.siswa.update', $siswa) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('pages.admin.siswa._form')

            <div class="mt-8 flex items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                <a href="{{ route('admin.siswa.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-white transition">
                    Perbarui Data Siswa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
