@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Tambah Jurusan / Kejuruan</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.jurusan.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kode Jurusan <span class="text-error-500">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode') }}" placeholder="Contoh: AKL, MPLB, BD" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm uppercase text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kode') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Singkatan Rombel <span class="text-xs text-gray-400">(Untuk pencocokan otomatis nama kelas)</span></label>
                    <input type="text" name="singkatan" value="{{ old('singkatan') }}" placeholder="Contoh: AK, MP, BD"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm uppercase text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('singkatan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Kompetensi Keahlian / Jurusan <span class="text-error-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Contoh: Akuntansi dan Keuangan Lembaga" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">UUID Jurusan Dapodik <span class="text-xs text-gray-400">(Opsional)</span></label>
                    <input type="text" name="dapodik_jurusan_id" value="{{ old('dapodik_jurusan_id') }}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('dapodik_jurusan_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center pt-6">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="is_aktif" value="0">
                        <input type="checkbox" name="is_aktif" value="1" {{ old('is_aktif', true) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        Jurusan Aktif
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi <span class="text-xs text-gray-400">(Opsional)</span></label>
                    <textarea name="deskripsi" rows="3"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.jurusan.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
