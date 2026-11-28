@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Tambah Mata Pelajaran</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.mata-pelajaran.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kode <span class="text-error-500">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode') }}" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kode') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Mata Pelajaran <span class="text-error-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kelompok Mata Pelajaran <span class="text-error-500">*</span></label>
                    <select name="kelompok"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach($kelompokOptions as $kKey => $kLabel)
                            <option value="{{ $kKey }}" {{ old('kelompok', 'umum') === $kKey ? 'selected' : '' }}>{{ $kLabel }}</option>
                        @endforeach
                    </select>
                    @error('kelompok') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Khusus Jurusan <span class="text-xs text-gray-400">(Kosongkan jika mapel umum semua jurusan)</span></label>
                    <select name="jurusan_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Umum — Semua Jurusan</option>
                        @foreach($jurusans as $jurusan)
                            <option value="{{ $jurusan->id }}" {{ old('jurusan_id') == $jurusan->id ? 'selected' : '' }}>
                                {{ $jurusan->kode }} — {{ $jurusan->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jurusan_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Berlaku Untuk Tingkat <span class="text-xs text-gray-400">(Kosong = semua tingkat)</span></label>
                    <div class="flex flex-wrap gap-4 pt-2">
                        @foreach($tingkatOptions as $t)
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="tingkat[]" value="{{ $t }}" {{ in_array($t, old('tingkat', []), true) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                Kelas {{ $t }}
                            </label>
                        @endforeach
                    </div>
                    @error('tingkat') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nilai KKM</label>
                        <input type="number" step="0.5" min="0" max="100" name="kkm" value="{{ old('kkm', 75) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('kkm') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Urutan Rapor</label>
                        <input type="number" min="1" max="999" name="urutan" value="{{ old('urutan', 100) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('urutan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="md:col-span-2">
                    <input type="hidden" name="tampil_di_rapor" value="0">
                    <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="tampil_di_rapor" value="1" {{ old('tampil_di_rapor', '1') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        Tampilkan Mata Pelajaran Ini di Buku Nilai &amp; Cetak Rapor (E-Rapor)
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
                    <textarea name="deskripsi" rows="3"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.mata-pelajaran.index') }}"
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
