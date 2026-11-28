@php
    $cards = collect($lmsClassCards ?? []);
    $guruNama = $guru?->nama_lengkap ?? auth()->user()?->name ?? 'Guru Pengampu';
    $uniqueClasses = $cards->pluck('kelas')->unique()->values();
    $dayTabs = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp

<section
    class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]"
    x-data="{
        selectedDay: 'semua',
        selectedKelas: 'semua',
        matchesFilter(days, kelas, isToday) {
            const dayOk = this.selectedDay === 'semua'
                || (this.selectedDay === 'hari_ini' && isToday)
                || (Array.isArray(days) && days.includes(this.selectedDay));
            const kelasOk = this.selectedKelas === 'semua' || this.selectedKelas === kelas;
            return dayOk && kelasOk;
        }
    }"
>
    <!-- Section Header (matching MyBest class card grid header) -->
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[#1d4ed8] dark:bg-blue-500/15 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-[#9f1239] dark:text-rose-400">
                        Kelas Mengajar &amp; LMS Guru
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Dikelompokkan per kelas &amp; mata pelajaran agar manajemen materi dan tugas lebih cepat.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-rose-600 px-3.5 py-1 text-xs font-bold text-white shadow-xs">
                {{ $cards->count() }} Kelas Mapel
            </span>
        </div>
    </div>

    @if($cards->isNotEmpty())
        <!-- Filter Bar: Hari & Kelas -->
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between rounded-xl border border-gray-100 bg-gray-50/70 p-3 dark:border-gray-800 dark:bg-gray-900/40">
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    @click="selectedDay = 'semua'"
                    :class="selectedDay === 'semua' ? 'bg-[#1d4ed8] text-white shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                >
                    Semua ({{ $cards->count() }})
                </button>
                <button
                    type="button"
                    @click="selectedDay = 'hari_ini'"
                    :class="selectedDay === 'hari_ini' ? 'bg-[#1d4ed8] text-white shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                >
                    Hari Ini ({{ $todayName ?? 'Aktif' }})
                </button>
                @foreach($dayTabs as $dayOpt)
                    <button
                        type="button"
                        @click="selectedDay = @js($dayOpt)"
                        :class="selectedDay === @js($dayOpt) ? 'bg-[#1d4ed8] text-white shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'"
                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                    >
                        {{ $dayOpt }}
                    </button>
                @endforeach
            </div>

            @if($uniqueClasses->count() > 1)
                <div class="flex items-center gap-2 shrink-0">
                    <label for="lms_filter_kelas" class="text-xs font-semibold text-gray-500 dark:text-gray-400">Filter Kelas:</label>
                    <select
                        id="lms_filter_kelas"
                        x-model="selectedKelas"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="semua">Semua Kelas</option>
                        @foreach($uniqueClasses as $klsOpt)
                            <option value="{{ $klsOpt }}">{{ $klsOpt }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- 3-Column Class Cards Grid -->
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($cards as $card)
                <div
                    x-show="matchesFilter(@js($card['days']), @js($card['kelas']), {{ $card['is_today'] ? 'true' : 'false' }})"
                    x-transition.opacity.duration.150ms
                    class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-150 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Blue Header Banner -->
                    <div class="bg-gradient-to-r from-[#1d4ed8] to-[#2563eb] p-5 text-white">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-md bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur-xs">
                                {{ $card['kelompok_badge'] }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-0.5 text-[11px] font-semibold text-gray-800 shadow-2xs">
                                <svg class="h-3 w-3 text-[#1d4ed8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $card['hari_label'] }}
                            </span>
                        </div>

                        <h4 class="mt-3 text-base font-bold uppercase leading-snug tracking-tight text-white line-clamp-2" title="{{ $card['mapel_nama'] }}">
                            {{ $card['mapel_nama'] }}
                        </h4>

                        <div class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-blue-100">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            <span>Kelas: {{ $card['kelas'] }}</span>
                        </div>
                    </div>

                    <!-- Card Body Metadata -->
                    <div class="flex flex-1 flex-col justify-between p-5">
                        <dl class="divide-y divide-gray-100 text-xs dark:divide-gray-800/80">
                            <div class="flex items-start justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Hari &amp; Jam:
                                </dt>
                                <dd class="text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $card['hari_jam_summary'] }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Guru:
                                </dt>
                                <dd class="text-right font-semibold text-gray-800 dark:text-gray-200 truncate" title="{{ $guruNama }}">
                                    {{ $guruNama }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Kode Mapel:
                                </dt>
                                <dd class="text-right font-mono font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $card['mapel_kode'] ?: '-' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Jumlah Siswa:
                                </dt>
                                <dd class="text-right font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $card['siswa_count'] }} Siswa
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Ruang:
                                </dt>
                                <dd class="text-right font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $card['lokasi_nama'] ?: '-' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 shrink-0">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Konten LMS:
                                </dt>
                                <dd class="flex flex-wrap items-center justify-end gap-1.5">
                                    <span class="rounded bg-teal-50 px-1.5 py-0.5 text-[11px] font-semibold text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                        {{ $card['materi_count'] }} Materi
                                    </span>
                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{ $card['tugas_count'] }} Tugas
                                    </span>
                                    @if($card['belum_dinilai_count'] > 0)
                                        <span class="rounded bg-rose-50 px-1.5 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300" title="Pengumpulan belum dinilai">
                                            {{ $card['belum_dinilai_count'] }} Perlu Nilai
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <!-- Action Buttons (Matching Reference Card Layout) -->
                        <div class="mt-4 space-y-2.5 border-t border-gray-100 pt-3.5 dark:border-gray-800">
                            <a
                                href="{{ route('guru.kelas.show', ['kelas' => $card['kelas'], 'mata_pelajaran_id' => $card['mapel_id']]) }}"
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-teal-500 bg-white px-4 py-2 text-xs font-bold text-teal-700 transition hover:bg-teal-50 dark:border-teal-500/70 dark:bg-transparent dark:text-teal-300 dark:hover:bg-teal-950/40"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Buka Kelas
                            </a>

                            <div class="grid grid-cols-3 gap-2">
                                <a
                                    href="{{ route('erapor.buku-nilai.index', ['kelas' => $card['kelas'], 'mata_pelajaran_id' => $card['mapel_id']]) }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-blue-500/70 bg-white px-2 py-2 text-[11px] font-bold text-blue-700 transition hover:bg-blue-50 dark:border-blue-500/60 dark:bg-transparent dark:text-blue-300 dark:hover:bg-blue-950/40"
                                    title="Buka Buku Nilai Mapel"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Nilai</span>
                                </a>

                                <a
                                    href="{{ route('guru.kelas.show', ['kelas' => $card['kelas'], 'mata_pelajaran_id' => $card['mapel_id'], 'tab' => 'materi']) }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-500/70 bg-white px-2 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/60 dark:bg-transparent dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                                    title="Kelola Materi Belajar"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <span>Materi</span>
                                </a>

                                <a
                                    href="{{ route('guru.kelas.show', ['kelas' => $card['kelas'], 'mata_pelajaran_id' => $card['mapel_id'], 'tab' => 'tugas']) }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-amber-500/80 bg-amber-50/40 px-2 py-2 text-[11px] font-bold text-amber-800 transition hover:bg-amber-50 dark:border-amber-500/60 dark:bg-amber-950/20 dark:text-amber-300 dark:hover:bg-amber-950/40"
                                    title="Kelola Tugas &amp; Penilaian"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    <span>Tugas</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-200 py-10 text-center dark:border-gray-800">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Belum ada jadwal kelas mengajar terdaftar.</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hubungi admin sekolah apabila jadwal mengajar Anda belum muncul.</p>
        </div>
    @endif
</section>
