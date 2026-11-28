@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" x-data="bukuNilai()">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Buku Nilai Mata Pelajaran</h2>
                @if($isGuru ?? false)
                    <span class="inline-flex items-center rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                        Sesuai Jadwal Mengajar Anda
                    </span>
                @endif
            </div>
            @if($selectedSubject && $selectedClass)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kelas: <span class="font-semibold text-brand-600">{{ $selectedClass }}</span> |
                    Mapel: <span class="font-semibold text-brand-600">{{ $selectedSubject->nama }}</span>
                </p>
            @else
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Belum ada kelas atau mata pelajaran yang dijadwalkan untuk akun Anda pada periode ini.
                </p>
            @endif
        </div>
        @if($selectedSubject && $selectedClass)
            <div class="flex flex-wrap items-center gap-2">
                <!-- Add Optional NH Block Button -->
                <form action="{{ route('erapor.buku-nilai.block.store') }}" method="POST" class="inline w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject->id }}">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-amber-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-amber-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Blok Nilai Harian (NH {{ $nextDailyBlock }})
                    </button>
                </form>

                <!-- Add Item Button -->
                <button @click="openAddModal(1, 'tugas')" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Butir Penilaian
                </button>

                <!-- Sync CBT Button (Open Modal) -->
                <button @click="openCbtModal = true" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Tarik Nilai CBT
                </button>

                <!-- Sync LMS Button (Open Modal) -->
                <button @click="openLmsModal = true" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-purple-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Tarik Tugas dari LMS
                </button>
            </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form id="filterBukuNilaiForm" method="GET" action="{{ route('erapor.buku-nilai.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Rombongan Belajar (Kelas)</label>
                <select id="filterBukuKelas" name="kelas" onchange="const m = document.getElementById('filterBukuMapel'); if (m) m.disabled = true; this.form.submit();" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @forelse($classes as $c)
                        <option value="{{ $c }}" {{ $selectedClass === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @empty
                        <option value="">Belum ada jadwal kelas</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Mata Pelajaran</label>
                <select id="filterBukuMapel" name="mata_pelajaran_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @forelse($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ $selectedSubjectId === $sb->id ? 'selected' : '' }}>{{ $sb->nama }}</option>
                    @empty
                        <option value="">Belum ada jadwal mapel</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Semester</label>
                <select name="semester" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="Ganjil" {{ $semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ $semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>
            </div>
        </form>
    </div>

    @if($selectedSubject && $selectedClass)
        <!-- Formula Info Strip -->
        <div class="mb-5 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-2.5 text-xs text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200">
            <span><strong>NA Harian:</strong> (40% &times; Rata-rata Tugas) + (60% &times; Rata-rata Projek/UH)</span>
            <span class="hidden sm:inline text-blue-300">|</span>
            <span><strong>Rata-rata SH:</strong> Rata-rata seluruh Projek/UH lintas blok NH</span>
            <span class="hidden sm:inline text-blue-300">|</span>
            <span><strong>Nilai Akhir:</strong> AVERAGE(NA Harian 1..n, Nilai Murni ASAT)</span>
        </div>

        @if(session('success'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Gradebook Matrix Table -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300 border-collapse">
                    <thead class="bg-gray-50 uppercase font-semibold text-gray-600 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-700 dark:text-gray-300">
                        <!-- Row 1: Group Headers -->
                        <tr>
                            <th rowspan="2" class="py-3 px-3 text-center w-10 border-r border-gray-200 dark:border-gray-700">No</th>
                            <th rowspan="2" class="py-3 px-3 min-w-[110px] border-r border-gray-200 dark:border-gray-700">NISN</th>
                            <th rowspan="2" class="py-3 px-4 min-w-[200px] sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                Nama Siswa
                            </th>

                            @foreach($dailyBlocks as $blockNum => $blockData)
                                @php $colCount = $blockData['items']->count() + 1; @endphp
                                <th colspan="{{ $colCount }}" class="py-2 px-3 text-center border-r border-gray-200 dark:border-gray-700 bg-indigo-50/70 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>NILAI HARIAN {{ $blockNum }}</span>
                                        <button type="button"
                                                @click="openAddModal({{ $blockNum }}, 'tugas')"
                                                title="Tambah kolom di Nilai Harian {{ $blockNum }}"
                                                class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-[11px] font-bold leading-none transition">
                                            +
                                        </button>
                                        @if($blockNum > 1)
                                            <form action="{{ route('erapor.buku-nilai.block.destroy', $blockNum) }}" method="POST" class="inline" onsubmit="return confirm('Hapus Blok Nilai Harian {{ $blockNum }} beserta seluruh nilainya?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                                                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject->id }}">
                                                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                                                <input type="hidden" name="semester" value="{{ $semester }}">
                                                <button type="submit" title="Hapus Blok NH {{ $blockNum }}" class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 text-[11px] font-bold leading-none transition">
                                                    &times;
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </th>
                            @endforeach

                            <th rowspan="2" class="py-3 px-2.5 text-center bg-sky-50 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300 border-r border-gray-200 dark:border-gray-700 min-w-[82px]">
                                Rata-rata<br>SH
                            </th>
                            <th colspan="{{ max(1, $asatItems->count()) }}" class="py-2 px-3 text-center bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-r border-gray-200 dark:border-gray-700">
                                ASAT / UAS
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border-r border-gray-200 dark:border-gray-700 min-w-[86px]">
                                Nilai Akhir<br>Mapel
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center min-w-[96px]">Status</th>
                        </tr>

                        <!-- Row 2: Sub-column Item Headers -->
                        <tr class="border-t border-gray-200 dark:border-gray-700 text-[11px]">
                            @foreach($dailyBlocks as $blockNum => $blockData)
                                @foreach($blockData['items'] as $item)
                                    @php
                                        $wg = $item->resolveWeightGroup();
                                        $isTugas = ($wg === 'tugas');
                                    @endphp
                                    <th class="py-2 px-2 text-center min-w-[92px] border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40" title="{{ $item->title }}">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="flex items-center gap-1 max-w-[105px]">
                                                <span class="truncate font-semibold text-gray-700 dark:text-gray-200">{{ $item->title }}</span>
                                                <form action="{{ route('erapor.buku-nilai.item.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kolom {{ addslashes($item->title) }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus kolom" class="text-gray-400 hover:text-red-600 font-bold leading-none">&times;</button>
                                                </form>
                                            </div>
                                            <span class="inline-block rounded px-1.5 py-0.5 text-[9px] font-bold {{ $isTugas ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' }}">
                                                {{ $isTugas ? 'Tugas 40%' : 'SH/UH 60%' }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="py-2 px-2 text-center min-w-[82px] border-r border-gray-200 dark:border-gray-700 bg-indigo-100/70 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-200">
                                    NA Harian {{ $blockNum }}
                                </th>
                            @endforeach

                            @foreach($asatItems as $asatItem)
                                <th class="py-2 px-2 text-center min-w-[94px] border-r border-gray-200 dark:border-gray-700 bg-blue-50/70 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="truncate">{{ $asatItem->title }}</span>
                                        @if($asatItems->count() > 1)
                                            <form action="{{ route('erapor.buku-nilai.item.destroy', $asatItem->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kolom {{ addslashes($asatItem->title) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus kolom" class="text-gray-400 hover:text-red-600 font-bold leading-none">&times;</button>
                                            </form>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @php
                            $totalMatrixCols = 7 + collect($dailyBlocks)->sum(fn ($b) => $b['items']->count() + 1) + max(1, $asatItems->count());
                        @endphp
                        @forelse($students as $idx => $siswa)
                            @php
                                $stScores = $scores[$siswa->id] ?? collect();
                                $scoreMap = $stScores->pluck('score', 'assessment_item_id');
                                $summary = $summaries[$siswa->id] ?? null;
                                $dailyScores = is_array($summary?->daily_scores) ? $summary->daily_scores : [];
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                <td class="py-2.5 px-3 text-center text-gray-400 font-mono border-r border-gray-100 dark:border-gray-800">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{{ $siswa->nisn }}</td>
                                <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white sticky left-0 z-10 bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                    {{ $siswa->nama_lengkap }}
                                </td>

                                @foreach($dailyBlocks as $blockNum => $blockData)
                                    @foreach($blockData['items'] as $item)
                                        @php $val = $scoreMap[$item->id] ?? null; @endphp
                                        <td class="py-2 px-1.5 text-center border-r border-gray-100 dark:border-gray-800">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   value="{{ $val !== null ? $val : '' }}"
                                                   placeholder="-"
                                                   @change="saveScore({{ $siswa->id }}, {{ $item->id }}, $event.target.value, $event.target)"
                                                   class="w-16 rounded-lg border border-gray-200 bg-white px-2 py-1 text-center text-xs font-mono focus:border-brand-500 focus:outline-none transition-colors duration-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                        </td>
                                    @endforeach
                                    @php $blockFinal = $dailyScores[(string) $blockNum]['final'] ?? null; @endphp
                                    <td id="na-harian-{{ $blockNum }}-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-bold font-mono text-indigo-700 dark:text-indigo-300 bg-indigo-50/40 dark:bg-indigo-950/20 border-r border-gray-200 dark:border-gray-700">
                                        {{ $blockFinal !== null ? number_format($blockFinal, 1) : '-' }}
                                    </td>
                                @endforeach

                                <td id="sh-avg-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-semibold font-mono text-sky-700 dark:text-sky-300 bg-sky-50/30 dark:bg-sky-900/10 border-r border-gray-200 dark:border-gray-700">
                                    {{ $summary?->sh_avg !== null ? number_format($summary->sh_avg, 1) : '-' }}
                                </td>

                                @foreach($asatItems as $asatItem)
                                    @php $asatVal = $scoreMap[$asatItem->id] ?? null; @endphp
                                    <td class="py-2 px-1.5 text-center bg-blue-50/20 dark:bg-blue-900/10 border-r border-gray-200 dark:border-gray-700">
                                        <input type="number"
                                               step="0.1"
                                               min="0"
                                               max="100"
                                               value="{{ $asatVal !== null ? $asatVal : '' }}"
                                               placeholder="-"
                                               @change="saveScore({{ $siswa->id }}, {{ $asatItem->id }}, $event.target.value, $event.target)"
                                               class="w-16 rounded-lg border border-blue-200 bg-white px-2 py-1 text-center text-xs font-mono font-semibold text-blue-700 focus:border-blue-500 focus:outline-none transition-colors duration-200 dark:border-blue-800 dark:bg-gray-800 dark:text-blue-300">
                                    </td>
                                @endforeach

                                <td id="final-score-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-bold font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/10 border-r border-gray-200 dark:border-gray-700">
                                    {{ $summary?->final_score !== null ? number_format($summary->final_score, 1) : '-' }}
                                </td>

                                <td id="status-cell-{{ $siswa->id }}" class="py-2.5 px-3 text-center">
                                    @php
                                        $statusBadge = match($summary?->status) {
                                            'siap_ditinjau', 'final', 'published' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                                            'belum_lengkap' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                            default => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
                                        };
                                        $statusLabel = match($summary?->status) {
                                            'siap_ditinjau', 'final', 'published' => 'Lengkap',
                                            'belum_lengkap' => 'Sebagian',
                                            default => 'Kosong',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $statusBadge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalMatrixCols }}" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada siswa di kelas {{ $selectedClass }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tarik dari LMS -->
        <div x-show="openLmsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div @click.away="openLmsModal = false" class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tarik Nilai dari LMS E-Learning</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Pilih tugas LMS yang nilainya ingin Anda petakan ke kolom Tugas (40%) pada Blok Nilai Harian.</p>

                <form action="{{ route('erapor.buku-nilai.sync-lms') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject->id }}">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tugas LMS Terkait</label>
                        <select name="tugas_id" required class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            @forelse($lmsTugasList as $t)
                                <option value="{{ $t->id }}">{{ $t->judul }} (Kelas: {{ $t->kelas }})</option>
                            @empty
                                <option value="" disabled>Belum ada tugas dibuat di mapel ini</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Petakan ke Kolom Tugas yang Ada (Opsional)</label>
                        <select name="target_assessment_item_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">Otomatis (Buat/cocokkan di Blok NH pilihan)</option>
                            @foreach($dailyBlocks as $bNum => $bData)
                                @foreach($bData['tugas'] as $tItem)
                                    <option value="{{ $tItem->id }}">[NH {{ $bNum }}] {{ $tItem->title }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Blok Nilai Harian Tujuan (jika otomatis)</label>
                        <select name="daily_block" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            @foreach($dailyBlocks as $bNum => $bData)
                                <option value="{{ $bNum }}">Nilai Harian {{ $bNum }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openLmsModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                        <button type="submit" class="rounded-xl bg-purple-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-purple-700 shadow-sm transition">Tarik Nilai</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Butir Penilaian -->
        <div x-show="openAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div @click.away="openAddItemModal = false" class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tambah Butir Penilaian Baru</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Tambahkan kolom nilai Tugas (40%), Projek/UH (60%), atau ASAT untuk mapel {{ $selectedSubject->nama }} kelas {{ $selectedClass }}.</p>

                <form action="{{ route('erapor.buku-nilai.item.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject->id }}">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kelompok Bobot / Kategori</label>
                        <select name="weight_group" x-model="newItemGroup" required class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="tugas">Tugas (Bobot 40% di dalam Blok NH)</option>
                            <option value="sumatif">Sumatif Harian / Projek / Ujian Harian (Bobot 60% di dalam Blok NH)</option>
                            <option value="asat">ASAT / UAS (Tambahan/Susulan)</option>
                        </select>
                    </div>

                    <div class="mb-4" x-show="newItemGroup !== 'asat'">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Blok Nilai Harian Tujuan</label>
                        <select name="daily_block" x-model="newItemBlock" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            @foreach($dailyBlocks as $bNum => $bData)
                                <option value="{{ $bNum }}">Nilai Harian {{ $bNum }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul Butir Penilaian</label>
                        <input type="text" name="title" required placeholder="Contoh: Tugas 2 / Projek 1 / Ujian Harian 1" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Skor Maksimal</label>
                        <input type="number" name="max_score" value="100" min="10" max="100" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openAddItemModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm transition">Simpan Kolom</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tarik Nilai dari CBT -->
        <div x-show="openCbtModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div @click.away="openCbtModal = false" class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tarik Nilai dari SMART-CBT</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Pilih ujian CBT yang ingin disinkronkan, tentukan kolom tujuan (Ujian Harian atau ASAT), serta strategi penilaian.</p>

                <form action="{{ route('erapor.buku-nilai.sync-cbt') }}" method="POST">
                    @csrf
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject->id }}">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Ujian di SMART-CBT</label>
                        <select name="cbt_exam_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">-- Seluruh Ujian Eligible Mapel Ini --</option>
                            @foreach($cbtExams as $exam)
                                <option value="{{ $exam['id'] }}">{{ $exam['nama'] }} [{{ $exam['kode_ujian'] }}] ({{ $exam['jenis'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Petakan ke Kolom Buku Nilai</label>
                        <select name="target_assessment_item_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">Otomatis (Nilai Murni ASAT)</option>
                            @foreach($dailyBlocks as $bNum => $bData)
                                @foreach($bData['items'] as $it)
                                    <option value="{{ $it->id }}">[NH {{ $bNum }}] {{ $it->title }}</option>
                                @endforeach
                            @endforeach
                            @foreach($asatItems as $asatIt)
                                <option value="{{ $asatIt->id }}">[ASAT] {{ $asatIt->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Strategi Sinkronisasi / Remedial</label>
                        <select name="sync_mode" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="replace">Ganti Nilai (Timpa semua nilai siswa dengan hasil ujian ini)</option>
                            <option value="highest">Remedial: Ambil Nilai Tertinggi (Bandingkan nilai lama & baru, ambil yang terbaik)</option>
                            <option value="fill_empty">Ujian Susulan: Hanya Isi Siswa Kosong (Jangan ubah nilai siswa yang sudah ada)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openCbtModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition">Mulai Tarik Nilai</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-8 text-center dark:border-amber-900/50 dark:bg-amber-950/20">
            <h3 class="text-base font-bold text-amber-900 dark:text-amber-300">Belum Ada Jadwal Mengajar Terdaftar</h3>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                Akun Anda belum memiliki jadwal mengajar kelas dan mata pelajaran pada tahun ajaran yang dipilih. Silakan hubungi admin/kurikulum untuk penugasan jadwal.
            </p>
        </div>
    @endif
</div>

<script>
function bukuNilai() {
    return {
        openLmsModal: false,
        openCbtModal: false,
        openAddItemModal: false,
        newItemBlock: 1,
        newItemGroup: 'tugas',
        openAddModal(blockNum, group) {
            this.newItemBlock = blockNum || 1;
            this.newItemGroup = group || 'tugas';
            this.openAddItemModal = true;
        },
        saveScore(siswaId, itemId, scoreVal, targetEl) {
            fetch("{{ route('erapor.buku-nilai.update-score') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    siswa_id: siswaId,
                    assessment_item_id: itemId,
                    score: scoreVal !== "" ? parseFloat(scoreVal) : null
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (targetEl) {
                        targetEl.classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
                        setTimeout(() => {
                            targetEl.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
                        }, 1200);
                    }
                    if (data.summary) {
                        const dailyScores = data.summary.daily_scores || {};
                        Object.keys(dailyScores).forEach(blockNum => {
                            const naEl = document.getElementById('na-harian-' + blockNum + '-' + siswaId);
                            if (naEl) {
                                const f = dailyScores[blockNum]?.final;
                                naEl.textContent = (f !== null && f !== undefined) ? Number(f).toFixed(1) : '-';
                            }
                        });

                        const shEl = document.getElementById('sh-avg-' + siswaId);
                        const finalEl = document.getElementById('final-score-' + siswaId);
                        if (shEl) shEl.textContent = data.summary.sh_avg !== null && data.summary.sh_avg !== undefined ? Number(data.summary.sh_avg).toFixed(1) : '-';
                        if (finalEl) finalEl.textContent = data.summary.final_score !== null && data.summary.final_score !== undefined ? Number(data.summary.final_score).toFixed(1) : '-';

                        const statusCell = document.getElementById('status-cell-' + siswaId);
                        if (statusCell) {
                            const st = data.summary.status;
                            let label = 'Kosong';
                            let cls = 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
                            if (st === 'siap_ditinjau' || st === 'final' || st === 'published') {
                                label = 'Lengkap';
                                cls = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
                            } else if (st === 'belum_lengkap') {
                                label = 'Sebagian';
                                cls = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
                            }
                            statusCell.innerHTML = `<span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full border ${cls}">${label}</span>`;
                        }
                    }
                } else {
                    alert('Gagal menyimpan nilai');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi');
            });
        }
    }
}
</script>
@endsection
