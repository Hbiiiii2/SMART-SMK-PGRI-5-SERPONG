@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Manajemen Tugas</h2>
        </div>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="{{ route('guru.dashboard') }}">Dashboard /</a></li>
                <li class="text-sm font-medium text-brand-500 dark:text-brand-400">Tugas</li>
            </ol>
        </nav>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-success-50 p-4 text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if(($lmsClassCards ?? collect())->isNotEmpty())
        <div class="mb-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Filter Tugas Berdasarkan Kelas Mengajar</h3>
                @if($selectedKelas || $selectedMapelId)
                    <a href="{{ route('guru.tugas.index') }}" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                        Tampilkan Semua Kelas
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($lmsClassCards as $card)
                    @php
                        $isActiveFilter = ($selectedKelas === $card['kelas'] && (int) $selectedMapelId === (int) $card['mapel_id']);
                    @endphp
                    <a
                        href="{{ route('guru.tugas.index', ['kelas' => $card['kelas'], 'mata_pelajaran_id' => $card['mapel_id']]) }}"
                        class="block rounded-xl border p-3.5 transition shadow-2xs {{ $isActiveFilter ? 'border-[#1d4ed8] bg-blue-50/70 ring-1 ring-[#1d4ed8] dark:border-blue-500 dark:bg-blue-950/30' : 'border-gray-200 bg-white hover:border-blue-300 dark:border-gray-800 dark:bg-white/[0.03]' }}"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded bg-[#1d4ed8] px-2 py-0.5 text-[10px] font-bold text-white">{{ $card['kelas'] }}</span>
                            <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300">{{ $card['tugas_count'] }} Tugas</span>
                        </div>
                        <div class="mt-2 truncate text-xs font-bold text-gray-800 dark:text-white" title="{{ $card['mapel_nama'] }}">{{ $card['mapel_nama'] }}</div>
                        <div class="mt-0.5 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                            <span>{{ $card['hari_label'] }}</span>
                            @if($card['belum_dinilai_count'] > 0)
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ $card['belum_dinilai_count'] }} Perlu Nilai</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Daftar Tugas {{ $selectedKelas ? "— Kelas {$selectedKelas}" : '' }}
                </h3>
            </div>
            <a href="{{ route('guru.tugas.create', array_filter(['kelas' => $selectedKelas, 'mata_pelajaran_id' => $selectedMapelId])) }}" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-center text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                Tambah Tugas
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Judul</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Tenggat Waktu</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($tugas as $t)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800 dark:text-white/90">
                                {{ $t->judul }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $t->mataPelajaran->nama ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $t->kelas }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 font-medium">
                                {{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-3 text-sm">
                                    <a href="{{ route('guru.tugas.export', $t) }}" class="text-success-500 hover:text-success-600 dark:text-success-400 dark:hover:text-success-300 font-medium">Export</a>
                                    <a href="{{ route('guru.tugas.show', $t) }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300 font-medium">Penilaian</a>
                                    <a href="{{ route('guru.tugas.edit', $t) }}" class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 font-medium">Edit</a>
                                    <form action="{{ route('guru.tugas.destroy', $t) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error-500 hover:text-error-600 dark:text-error-400 dark:hover:text-error-300 font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada tugas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
