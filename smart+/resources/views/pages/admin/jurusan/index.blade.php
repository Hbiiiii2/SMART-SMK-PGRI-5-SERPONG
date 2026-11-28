@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Master Data Jurusan / Kejuruan</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola kompetensi keahlian SMK untuk pemetaan rombel dan pemisahan mata pelajaran umum vs kejuruan.</p>
        </div>
        <a href="{{ route('admin.jurusan.create') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Jurusan
        </a>
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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-4">
            <form method="GET" action="{{ route('admin.jurusan.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode, singkatan, atau nama jurusan..."
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-80" />
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Cari
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Kode / Singkatan</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Kompetensi Keahlian</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400 text-center">Rombel</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400 text-center">Mapel Khusus</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($jurusans as $jurusan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ ($jurusans->currentPage() - 1) * $jurusans->perPage() + $loop->iteration }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-md bg-brand-50 px-2.5 py-0.5 font-mono text-xs font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">{{ $jurusan->kode }}</span>
                                @if($jurusan->singkatan)
                                    <span class="ml-1 inline-flex rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $jurusan->singkatan }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-800 dark:text-white/90">{{ $jurusan->nama }}</div>
                                @if($jurusan->deskripsi)
                                    <div class="text-xs text-gray-400">{{ Str::limit($jurusan->deskripsi, 70) }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('admin.kelas.index', ['jurusan_id' => $jurusan->id]) }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                    {{ $jurusan->rombels_count }} rombel
                                </a>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('admin.mata-pelajaran.index', ['jurusan_id' => $jurusan->id]) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ $jurusan->mata_pelajarans_count }} mapel
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                @if($jurusan->is_aktif)
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.jurusan.edit', $jurusan) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.jurusan.destroy', $jurusan) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus jurusan {{ $jurusan->nama }}?')"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data jurusan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $jurusans->links() }}
        </div>
    </div>
</div>
@endsection
