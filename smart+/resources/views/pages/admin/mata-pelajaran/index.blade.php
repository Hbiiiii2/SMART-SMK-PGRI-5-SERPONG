@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Data Mata Pelajaran</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelompokkan mapel Umum vs Kejuruan dan petakan ke Jurusan &amp; Tingkat untuk E-Rapor SMK.</p>
        </div>
        <a href="{{ route('admin.mata-pelajaran.create') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Mata Pelajaran
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
        <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <form method="GET" action="{{ route('admin.mata-pelajaran.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama mapel..."
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                <select name="kelompok"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Kelompok</option>
                    @foreach($kelompokOptions as $kKey => $kLabel)
                        <option value="{{ $kKey }}" {{ request('kelompok') === $kKey ? 'selected' : '' }}>{{ $kLabel }}</option>
                    @endforeach
                </select>

                <select name="jurusan_id"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Jurusan</option>
                    <option value="umum" {{ request('jurusan_id') === 'umum' ? 'selected' : '' }}>Umum (Semua Jurusan)</option>
                    @foreach($jurusans as $j)
                        <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->kode }} — {{ $j->nama }}</option>
                    @endforeach
                </select>

                <select name="tingkat"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Tingkat</option>
                    @foreach($tingkatOptions as $t)
                        <option value="{{ $t }}" {{ request('tingkat') === $t ? 'selected' : '' }}>Tingkat {{ $t }}</option>
                    @endforeach
                </select>

                <select name="tampil_di_rapor"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Status Rapor</option>
                    <option value="1" {{ request('tampil_di_rapor') === '1' ? 'selected' : '' }}>Tampil di Rapor</option>
                    <option value="0" {{ request('tampil_di_rapor') === '0' ? 'selected' : '' }}>Jadwal Saja</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex-1 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'kelompok', 'jurusan_id', 'tingkat', 'tampil_di_rapor']))
                        <a href="{{ route('admin.mata-pelajaran.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Mata Pelajaran</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Kelompok</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Jurusan / Tingkat</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400 text-center">KKM</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($mataPelajarans as $mapel)
                        @php
                            $kelompokColor = match($mapel->kelompok ?? 'umum') {
                                'kejuruan' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                                'konsentrasi' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                                'pilihan' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                'mulok' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ ($mataPelajarans->currentPage() - 1) * $mataPelajarans->perPage() + $loop->iteration }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-800 dark:text-white/90">{{ $mapel->kode }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-gray-800 dark:text-white/90 font-medium">{{ $mapel->nama }}</span>
                                    @if($mapel->tampil_di_rapor ?? true)
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Rapor</span>
                                    @else
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">Jadwal Saja</span>
                                    @endif
                                </div>
                                @if($mapel->deskripsi && $mapel->deskripsi !== $mapel->nama)
                                    <div class="text-xs text-gray-400">{{ Str::limit($mapel->deskripsi, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $kelompokColor }}">
                                    {{ ucfirst($mapel->kelompok ?? 'umum') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($mapel->jurusan)
                                    <span class="inline-flex rounded-md bg-brand-50 px-2 py-0.5 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">{{ $mapel->jurusan->kode }}</span>
                                @else
                                    <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Semua Jurusan</span>
                                @endif
                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">· {{ $mapel->tingkat_label }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90 text-center font-mono font-bold">{{ $mapel->kkm !== null ? (floor($mapel->kkm) == $mapel->kkm ? number_format($mapel->kkm, 0) : number_format($mapel->kkm, 1)) : '75' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.mata-pelajaran.edit', $mapel) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.mata-pelajaran.destroy', $mapel) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus?')"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data mata pelajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $mataPelajarans->links() }}
        </div>
    </div>
</div>
@endsection
