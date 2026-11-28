@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Kelas / Rombel: {{ $rombel->nama }}</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.kelas.update', $rombel) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Kelas / Rombel <span class="text-error-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $rombel->nama) }}" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <p class="mt-1 text-xs text-gray-400">Mengubah nama rombel otomatis menyinkronkan label kelas di Siswa, Jadwal, dan Wali Kelas.</p>
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Tingkat</label>
                    <select name="tingkat"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Pilih Tingkat --</option>
                        @foreach($tingkatOptions as $t)
                            <option value="{{ $t }}" {{ old('tingkat', $rombel->tingkat) === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('tingkat') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Tahun Ajaran <span class="text-error-500">*</span></label>
                    <select name="tahun_ajaran_id" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" {{ old('tahun_ajaran_id', $rombel->tahun_ajaran_id) == $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('tahun_ajaran_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Semester <span class="text-error-500">*</span></label>
                    <select name="semester" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="Ganjil" {{ old('semester', $rombel->semester) === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="Genap" {{ old('semester', $rombel->semester) === 'Genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                    @error('semester') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Jurusan / Kompetensi Keahlian
                        <a href="{{ route('admin.jurusan.index') }}" class="ml-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">(Kelola Master Jurusan)</a>
                    </label>
                    <select name="jurusan_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Otomatis dari Nama Rombel --</option>
                        @foreach($jurusans ?? [] as $jurusan)
                            <option value="{{ $jurusan->id }}" {{ old('jurusan_id', $rombel->jurusan_id) == $jurusan->id ? 'selected' : '' }}>
                                {{ $jurusan->kode }} — {{ $jurusan->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jurusan_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Wali Kelas</label>
                    <select name="wali_kelas_guru_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Belum Ditentukan --</option>
                        @foreach($gurus as $guru)
                            <option value="{{ $guru->id }}" {{ old('wali_kelas_guru_id', $rombel->wali_kelas_guru_id) == $guru->id ? 'selected' : '' }}>{{ $guru->nama_lengkap }}</option>
                        @endforeach
                    </select>
                    @error('wali_kelas_guru_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kurikulum</label>
                    <input type="text" name="kurikulum" value="{{ old('kurikulum', $rombel->kurikulum) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kurikulum') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kapasitas Siswa</label>
                    <input type="number" name="kapasitas" min="1" max="100" value="{{ old('kapasitas', $rombel->kapasitas) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kapasitas') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">UUID Rombel Dapodik</label>
                    <input type="text" name="dapodik_rombongan_belajar_id" value="{{ old('dapodik_rombongan_belajar_id', $rombel->dapodik_rombongan_belajar_id) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm font-mono text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('dapodik_rombongan_belajar_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center pt-6">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="is_aktif" value="0">
                        <input type="checkbox" name="is_aktif" value="1" {{ old('is_aktif', $rombel->is_aktif) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        Rombel Aktif
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.kelas.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Perbarui
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
