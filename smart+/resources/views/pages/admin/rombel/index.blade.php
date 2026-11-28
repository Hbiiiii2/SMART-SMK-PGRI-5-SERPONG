@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Master Data Kelas / Rombel</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola rombongan belajar resmi sekolah (terintegrasi ke Siswa, Jadwal, E-Rapor &amp; Dapodik).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.siswa.dapodik.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                Hub Dapodik
            </a>
            <a href="{{ route('admin.kelas.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kelas / Rombel
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
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Rombel Terdaftar</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalRombel }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rombel Aktif</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $aktifRombel }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Siswa Terhubung ke Master Rombel</p>
            <p class="mt-2 text-2xl font-bold text-brand-600 dark:text-brand-400">{{ $totalSiswaTerpetakan }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-4">
            <form method="GET" action="{{ route('admin.kelas.index') }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas atau jurusan..."
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-72" />
                    <select name="tingkat"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-40">
                        <option value="">Semua Tingkat</option>
                        @foreach($tingkatOptions as $t)
                            <option value="{{ $t }}" {{ request('tingkat') === $t ? 'selected' : '' }}>Tingkat {{ $t }}</option>
                        @endforeach
                    </select>
                    <select name="jurusan_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-56">
                        <option value="">Semua Jurusan</option>
                        @foreach($jurusans ?? [] as $j)
                            <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->kode }} — {{ $j->nama }}</option>
                        @endforeach
                    </select>
                    <select name="tahun_ajaran_id"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-48">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" {{ request('tahun_ajaran_id') == $ta->id ? 'selected' : '' }}>{{ $ta->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Rombel</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Tingkat / Jurusan</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Wali Kelas</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Tahun Ajaran</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Siswa</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($rombels as $rombel)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ ($rombels->currentPage() - 1) * $rombels->perPage() + $loop->iteration }}</td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-800 dark:text-white/90">{{ $rombel->nama }}</div>
                                @if($rombel->dapodik_rombongan_belajar_id)
                                    <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">UUID Dapodik Terhubung</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $rombel->tingkat ?? '-' }}</span>
                                @if($rombel->jurusan)
                                    <span class="ml-1 inline-flex rounded-md bg-brand-50 px-2 py-0.5 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">{{ $rombel->jurusan->kode }}</span>
                                @endif
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $rombel->jurusan?->nama ?? $rombel->kompetensi_keahlian ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $rombel->waliKelas?->nama_lengkap ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $rombel->tahunAjaran?->nama ?? '-' }} ({{ $rombel->semester }})</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.siswa.index', ['rombel_id' => $rombel->id]) }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                    {{ $rombel->siswas_count }} siswa
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                @if($rombel->is_aktif)
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.kelas.edit', $rombel) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.kelas.destroy', $rombel) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus kelas {{ $rombel->nama }}?')"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data kelas / rombel.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $rombels->links() }}
        </div>
    </div>
</div>
@endsection
