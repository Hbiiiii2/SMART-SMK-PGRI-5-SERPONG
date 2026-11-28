@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Repository Data Siswa</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data induk peserta didik terstruktur standar Dapodik &amp; master kelas sekolah.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.siswa.dapodik.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400">
                Impor / Sinkron Dapodik
            </a>
            <a href="{{ route('admin.siswa.dapodik.export', request()->only(['rombel_id', 'status_siswa', 'tahun_ajaran_id'])) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                Ekspor Excel
            </a>
            <a href="{{ route('admin.siswa.kenaikan-kelas') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                Kenaikan Kelas
            </a>
            <a href="{{ route('admin.siswa.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition">
                + Tambah Siswa
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Repository Siswa</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalSiswa }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Siswa Aktif</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $aktifSiswa }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Lulus / Mutasi / Nonaktif</p>
            <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $nonAktifSiswa }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-4">
            <form method="GET" action="{{ route('admin.siswa.index') }}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama, NIPD, NISN, atau NIK..."
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 lg:w-72" />
                    <select name="rombel_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 lg:w-48">
                        <option value="">Semua Kelas / Rombel</option>
                        @foreach($rombels as $r)
                            <option value="{{ $r->id }}" {{ request('rombel_id') == $r->id ? 'selected' : '' }}>{{ $r->nama }}</option>
                        @endforeach
                    </select>
                    <select name="status_siswa"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 lg:w-44">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status_siswa') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="lulus" {{ request('status_siswa') === 'lulus' ? 'selected' : '' }}>Lulus / Alumni</option>
                        <option value="mutasi_keluar" {{ request('status_siswa') === 'mutasi_keluar' ? 'selected' : '' }}>Mutasi Keluar</option>
                    </select>
                    <select name="tahun_ajaran_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 lg:w-48">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" {{ request('tahun_ajaran_id') == $ta->id ? 'selected' : '' }}>{{ $ta->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                        Cari
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">NIPD / NISN</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Siswa</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas / Rombel</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Kelengkapan</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($siswas as $siswa)
                        @php $comp = $siswa->data_completeness; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ ($siswas->currentPage() - 1) * $siswas->perPage() + $loop->iteration }}</td>
                            <td class="px-5 py-3">
                                <div class="font-mono text-xs text-gray-800 dark:text-white/90">NIPD: {{ $siswa->nipd ?? '-' }}</div>
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">NISN: {{ $siswa->nisn ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.siswa.show', $siswa) }}" class="font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">
                                    {{ $siswa->nama_lengkap }}
                                </a>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $siswa->jenis_kelamin ?? '-' }} · {{ $siswa->user ? 'Akun Login Aktif' : 'Tanpa Akun Login' }}
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $siswa->rombel?->nama ?? $siswa->kelas ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($siswa->status_siswa ?? 'aktif') === 'aktif' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                    {{ $siswa->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $comp['score'] >= 90 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : ($comp['score'] >= 60 ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400') }}">
                                    {{ $comp['score'] }}%
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.siswa.show', $siswa) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.siswa.edit', $siswa) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.siswa.destroy', $siswa) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus data siswa {{ $siswa->nama_lengkap }}?')"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-2.5 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $siswas->links() }}
        </div>
    </div>
</div>
@endsection
