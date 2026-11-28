@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" x-data="{ activeTab: @js($activeTab ?? 'materi') }">
    <!-- Breadcrumb & Back -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
            &larr; Kembali ke Dashboard Guru
        </a>
        <nav>
            <ol class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('guru.dashboard') }}" class="hover:text-brand-600">Dashboard</a> /</li>
                <li class="font-semibold text-brand-600 dark:text-brand-400">Ruang Kelas {{ $classCard['kelas'] }}</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
        <div class="mb-6 flex items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            <div class="flex items-center gap-2.5 text-sm font-semibold">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-xs font-bold text-white">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    <!-- Classroom Banner Header -->
    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="bg-gradient-to-r from-[#1d4ed8] to-[#2563eb] p-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/20 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider">
                            {{ $classCard['kelompok_badge'] }}
                        </span>
                        <span class="rounded-md bg-white px-2.5 py-0.5 text-[11px] font-bold text-gray-800">
                            {{ $classCard['hari_label'] }}
                        </span>
                        <span class="rounded-md bg-blue-900/40 px-2.5 py-0.5 font-mono text-[11px] font-semibold">
                            Kode: {{ $classCard['mapel_kode'] ?: '-' }}
                        </span>
                    </div>
                    <h2 class="mt-3 text-xl sm:text-2xl font-bold uppercase tracking-tight">
                        {{ $classCard['mapel_nama'] }}
                    </h2>
                    <p class="mt-1 text-sm text-blue-100 font-medium">
                        Kelas {{ $classCard['kelas'] }} · {{ $classCard['hari_jam_summary'] }} · {{ $classCard['lokasi_nama'] }} · {{ $classCard['siswa_count'] }} Siswa
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('guru.materi.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#1d4ed8] shadow-sm hover:bg-blue-50 transition"
                    >
                        + Tambah Materi
                    </a>
                    <a
                        href="{{ route('guru.tugas.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-bold text-gray-900 shadow-sm hover:bg-amber-300 transition"
                    >
                        + Tambah Tugas
                    </a>
                    <a
                        href="{{ route('erapor.buku-nilai.index', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id']]) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-white/40 bg-white/15 px-4 py-2.5 text-xs font-bold text-white hover:bg-white/25 transition"
                    >
                        Buka Buku Nilai
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 bg-gray-50/70 px-6 py-3 dark:border-gray-800 dark:bg-gray-900/60">
            <button
                type="button"
                @click="activeTab = 'materi'"
                :class="activeTab === 'materi' ? 'bg-teal-600 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
            >
                <span>Materi Belajar</span>
                <span class="rounded-full px-2 py-0.5 text-[11px]" :class="activeTab === 'materi' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">{{ $materi->count() }}</span>
            </button>
            <button
                type="button"
                @click="activeTab = 'tugas'"
                :class="activeTab === 'tugas' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
            >
                <span>Tugas &amp; Penilaian</span>
                <span class="rounded-full px-2 py-0.5 text-[11px]" :class="activeTab === 'tugas' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">{{ $tugas->count() }}</span>
            </button>
        </div>
    </div>

    <!-- Tab 1: Materi Belajar -->
    <div x-show="activeTab === 'materi'" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Daftar Materi — {{ $classCard['kelas'] }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Materi pembelajaran khusus untuk {{ $classCard['mapel_nama'] }} di kelas {{ $classCard['kelas'] }}.</p>
            </div>
            <a href="{{ route('guru.materi.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700 transition">
                + Tambah Materi
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3.5">No</th>
                        <th class="px-6 py-3.5">Judul Materi</th>
                        <th class="px-6 py-3.5">Tipe</th>
                        <th class="px-6 py-3.5">Tanggal Dibuat</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($materi as $idx => $m)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-xs text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="px-6 py-3.5 font-semibold text-gray-800 dark:text-white">{{ $m->judul }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex rounded-md bg-teal-50 px-2 py-0.5 text-[11px] font-bold uppercase text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                    {{ $m->tipe_materi }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-gray-500 dark:text-gray-400">{{ $m->created_at?->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex items-center gap-3 text-xs font-semibold">
                                    <a href="{{ route('guru.materi.show', $m) }}" class="text-brand-600 hover:underline dark:text-brand-400">Lihat</a>
                                    <a href="{{ route('guru.materi.edit', $m) }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Edit</a>
                                    <form action="{{ route('guru.materi.destroy', $m) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus materi ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada materi untuk kelas {{ $classCard['kelas'] }}.</p>
                                <a href="{{ route('guru.materi.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}" class="mt-3 inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">
                                    + Upload Materi Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Tugas & Penilaian -->
    <div x-show="activeTab === 'tugas'" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Daftar Tugas — {{ $classCard['kelas'] }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tugas, pengumpulan siswa, dan penilaian untuk {{ $classCard['mapel_nama'] }} di kelas {{ $classCard['kelas'] }}.</p>
            </div>
            <a href="{{ route('guru.tugas.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition">
                + Tambah Tugas
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3.5">No</th>
                        <th class="px-6 py-3.5">Judul Tugas</th>
                        <th class="px-6 py-3.5">Tenggat Waktu</th>
                        <th class="px-6 py-3.5 text-center">Pengumpulan</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($tugas as $idx => $t)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-xs text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="px-6 py-3.5 font-semibold text-gray-800 dark:text-white">{{ $t->judul }}</td>
                            <td class="px-6 py-3.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $t->pengumpulan_tugas_count }} Masuk
                                    </span>
                                    @if(($t->belum_dinilai_count ?? 0) > 0)
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                            {{ $t->belum_dinilai_count }} Belum Dinilai
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex items-center gap-3 text-xs font-semibold">
                                    <a href="{{ route('guru.tugas.export', $t) }}" class="text-emerald-600 hover:underline dark:text-emerald-400">Export</a>
                                    <a href="{{ route('guru.tugas.show', $t) }}" class="text-brand-600 hover:underline dark:text-brand-400">Penilaian</a>
                                    <a href="{{ route('guru.tugas.edit', $t) }}" class="text-gray-600 hover:text-brand-600 dark:text-gray-400">Edit</a>
                                    <form action="{{ route('guru.tugas.destroy', $t) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada tugas untuk kelas {{ $classCard['kelas'] }}.</p>
                                <a href="{{ route('guru.tugas.create', ['kelas' => $classCard['kelas'], 'mata_pelajaran_id' => $classCard['mapel_id'], 'redirect_to' => 'kelas']) }}" class="mt-3 inline-flex items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700">
                                    + Buat Tugas Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
