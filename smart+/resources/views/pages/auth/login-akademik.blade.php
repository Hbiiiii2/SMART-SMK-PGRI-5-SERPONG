@php
    try {
        $pengaturan = \App\Models\PengaturanSekolah::first();
    } catch (\Throwable $e) {
        $pengaturan = null;
    }
    $namaSekolah = $pengaturan->nama_sekolah ?? 'SMK PGRI 5 Serpong';
    $tahunAjaranAktif = null;
    try {
        $tahunAjaranAktif = \App\Models\TahunAjaran::where('is_active', true)->value('nama');
    } catch (\Throwable $e) {
        $tahunAjaranAktif = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Portal Admin Akademik | SMART+ {{ $namaSekolah }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('pages.auth.partials.airtable-tokens')

    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            if ((savedTheme || systemTheme) === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>

<body class="at-body at-grid-bg min-h-[100dvh] antialiased">
    <div class="mx-auto flex min-h-[100dvh] max-w-[1200px] flex-col justify-center px-4 py-6 sm:px-6 lg:px-8">

        <!-- Main Asymmetric Enterprise Split: Left Auth Form, Right Airtable Academic Base Grid -->
        <main class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-12 lg:gap-8">

            <!-- Left Column (5 Cols): Portal Admin Akademik Login Form -->
            <section aria-labelledby="akademik-login-heading" class="at-card overflow-hidden lg:col-span-5 flex flex-col justify-between">
                <div>
                    <div class="h-1.5 w-full bg-[#1b61c9]"></div>

                    <div class="p-6 sm:p-8">
                        <!-- Institutional Header -->
                        <div class="flex items-center justify-between gap-3 pb-6 border-b border-[var(--at-border-soft)]">
                            <div class="flex items-center gap-3 min-w-0">
                                @if(!empty($pengaturan->logo))
                                    <img src="{{ asset('storage/' . $pengaturan->logo) }}" width="44" height="44" alt="Logo {{ $namaSekolah }}" class="h-11 w-11 shrink-0 rounded-[12px] object-cover ring-1 ring-[var(--at-border)]">
                                @else
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-[#1b61c9] text-base font-bold text-white shadow-sm">
                                        S+
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--at-fg)]">{{ $namaSekolah }}</p>
                                    <p class="truncate font-mono text-[11px] text-[var(--at-muted)]" translate="no">akademik.smkpgri5serpong.sch.id</p>
                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#E8F5EC] px-2.5 py-1 text-[11px] font-semibold text-[#006400] dark:bg-emerald-500/15 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                Akademik
                            </span>
                        </div>

                        <!-- Portal Title & Description -->
                        <div class="mt-6">
                            <span class="at-base-chip">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#1b61c9]" aria-hidden="true"></span>
                                <span>Portal Khusus Admin &amp; Tata Usaha</span>
                            </span>

                            <h1 id="akademik-login-heading" class="at-heading mt-4 text-2xl sm:text-[26px] font-semibold">
                                Masuk Manajemen Akademik
                            </h1>

                            <p class="mt-2.5 text-sm leading-[1.45] tracking-[0.1px] text-[var(--at-muted)]">
                                Konsol kendali administrasi sekolah, master data guru &amp; siswa, konfigurasi presensi RFID/GPS, kalender akademik, dan pengelolaan E-Rapor.
                            </p>
                        </div>

                        @if ($errors->any())
                            <div role="alert" aria-live="polite" class="mt-5 rounded-[12px] border border-[#dc2626]/30 bg-red-50 p-3.5 text-xs font-medium text-[#b91c1c] dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-300">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Auth Form -->
                        <form
                            method="POST"
                            action="{{ route('login') }}"
                            x-data="{ submitting: false }"
                            @submit="submitting = true"
                            class="mt-6 space-y-4"
                        >
                            @csrf
                            <input type="hidden" name="portal" value="akademik">

                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-medium tracking-[0.07px] text-[var(--at-fg)]">
                                    Email Administrator / Staf <span class="text-[#dc2626]">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    spellcheck="false"
                                    placeholder="nama.admin@smkpgri5serpong.sch.id…"
                                    class="at-input"
                                >
                                <p class="mt-1.5 text-xs tracking-[0.07px] text-[var(--at-muted)]">
                                    Khusus Admin Sekolah, Kepala Sekolah, Direktur, dan Staf Akademik.
                                </p>
                            </div>

                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-medium tracking-[0.07px] text-[var(--at-fg)]">
                                    Kata Sandi <span class="text-[#dc2626]">*</span>
                                </label>
                                <div x-data="{ showPassword: false }" class="relative">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        id="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Masukkan kata sandi akun…"
                                        class="at-input pr-12"
                                    >
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        class="absolute top-1/2 right-1.5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-[10px] text-[var(--at-muted)] transition-colors duration-150 hover:bg-slate-100 hover:text-[var(--at-fg)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1b61c9] dark:hover:bg-slate-800"
                                    >
                                        <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg x-show="showPassword" style="display: none;" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58a2.25 2.25 0 003.18 3.18M9.88 4.24A10.45 10.45 0 0112 4.05c6 0 9.75 6.75 9.75 6.75a16.86 16.86 0 01-3.27 4.28M6.61 6.61A16.91 16.91 0 002.25 12s3.75 6.75 9.75 6.75c1.56 0 3-.34 4.29-.93" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 pt-1">
                                <label for="remember" x-data="{ checked: {{ old('remember') ? 'true' : 'false' }} }" class="relative inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm font-medium text-[var(--at-fg)] select-none">
                                    <input type="checkbox" id="remember" name="remember" value="1" x-model="checked" {{ old('remember') ? 'checked' : '' }} class="peer sr-only">
                                    <span
                                        :class="checked ? 'border-[#1b61c9] bg-[#1b61c9] text-white' : 'border-[var(--at-border)] bg-[var(--at-surface)] text-transparent'"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border transition-colors duration-150 peer-checked:border-[#1b61c9] peer-checked:bg-[#1b61c9] peer-checked:text-white peer-checked:[&>svg]:opacity-100 peer-focus-visible:ring-2 peer-focus-visible:ring-[#1b61c9]/40"
                                    >
                                        <svg :class="checked ? 'opacity-100' : 'opacity-0'" class="h-3.5 w-3.5 transition-opacity duration-150" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                            <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span>Ingat saya</span>
                                </label>

                                <span class="text-xs font-medium text-[var(--at-muted)]">Audit Log Aktif</span>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="at-btn-primary inline-flex w-full items-center justify-center gap-2 px-6 py-3.5 disabled:opacity-75"
                            >
                                <svg x-show="submitting" style="display: none;" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-85" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Memverifikasi sesi…' : 'Masuk ke Manajemen Akademik'">Masuk ke Manajemen Akademik</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="border-t border-[var(--at-border-soft)] bg-slate-50/70 px-6 py-3.5 text-xs text-[var(--at-muted)] dark:bg-slate-900/40 sm:px-8">
                    <div class="flex items-center justify-between gap-2">
                        <span>SMART+ Admin Console</span>
                        <span class="font-semibold text-[#1b61c9] dark:text-[#66A8FF]" translate="no">Aybisa Studio</span>
                    </div>
                </div>
            </section>

            <!-- Right Column (7 Cols): Airtable Structured Database Overview -->
            <aside aria-label="Ringkasan Workspace Manajemen Akademik" class="at-card flex flex-col justify-between overflow-hidden lg:col-span-7">
                <div>
                    <!-- Airtable Base Toolbar Strip -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--at-border-soft)] px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-[8px] bg-[#1b61c9]/10 text-[#1b61c9]" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <path d="M3 10h18M9 4v16" />
                                </svg>
                            </span>
                            <span class="text-sm font-semibold text-[var(--at-fg)]">Workspace Administrasi &amp; Data Sekolah</span>
                        </div>
                        @if($tahunAjaranAktif)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--at-border)] bg-[var(--at-surface)] px-3 py-1 text-xs font-medium tabular-nums text-[var(--at-fg)]">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#1b61c9]" aria-hidden="true"></span>
                                TA {{ $tahunAjaranAktif }}
                            </span>
                        @endif
                    </div>

                    <div class="p-6 sm:p-7 space-y-6">
                        <!-- Airtable Signature Colored Base Chips -->
                        <div>
                            <p class="mb-2.5 text-xs font-medium tracking-[0.14px] text-[var(--at-muted)]">Modul Terintegrasi di Portal Ini</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="at-base-chip"><span class="h-2.5 w-2.5 rounded-full bg-[#1b61c9]" aria-hidden="true"></span>Data Induk &amp; Rombel</span>
                                <span class="at-base-chip"><span class="h-2.5 w-2.5 rounded-full bg-[#16a34a]" aria-hidden="true"></span>Presensi RFID &amp; GPS</span>
                                <span class="at-base-chip"><span class="h-2.5 w-2.5 rounded-full bg-[#f5a623]" aria-hidden="true"></span>Jadwal &amp; Kalender</span>
                                <span class="at-base-chip"><span class="h-2.5 w-2.5 rounded-full bg-[#ec4899]" aria-hidden="true"></span>E-Rapor &amp; Buku Induk</span>
                            </div>
                        </div>

                        <!-- Structured Airtable Record Grid -->
                        <div class="overflow-hidden rounded-[12px] border border-[var(--at-border)]">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-[var(--at-border)] bg-slate-50/90 text-[11px] font-semibold uppercase tracking-[0.28px] text-[var(--at-muted)] dark:bg-slate-800/60">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5">Basis Data Akademik</th>
                                        <th scope="col" class="px-4 py-2.5 hidden sm:table-cell">Akses Wewenang</th>
                                        <th scope="col" class="px-4 py-2.5 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--at-border-soft)] bg-[var(--at-surface)] text-[var(--at-fg-2)]">
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-[var(--at-fg)]">Master Siswa, Guru &amp; Kartu RFID</td>
                                        <td class="px-4 py-3 hidden sm:table-cell text-[var(--at-muted)]">Admin Sekolah / TU</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F5EC] px-2.5 py-0.5 text-[11px] font-medium text-[#006400] dark:bg-emerald-500/15 dark:text-emerald-300">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>Sinkron
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-[var(--at-fg)]">Monitoring Kehadiran &amp; Peta GPS</td>
                                        <td class="px-4 py-3 hidden sm:table-cell text-[var(--at-muted)]">Kepala Sekolah &amp; Admin</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF3FF] px-2.5 py-0.5 text-[11px] font-medium text-[#1b61c9] dark:bg-blue-500/15 dark:text-blue-300">
                                                Real-time
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-[var(--at-fg)]">Penjadwalan KBM &amp; Hari Libur</td>
                                        <td class="px-4 py-3 hidden sm:table-cell text-[var(--at-muted)]">Staf Akademik</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF7E6] px-2.5 py-0.5 text-[11px] font-medium text-[#9C6200] dark:bg-amber-500/15 dark:text-amber-300">
                                                Terjadwal
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-[var(--at-fg)]">E-Rapor, Cetak STS &amp; Ekspor Laporan</td>
                                        <td class="px-4 py-3 hidden sm:table-cell text-[var(--at-muted)]">Admin, Direktur &amp; Kepsek</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FCE7F3] px-2.5 py-0.5 text-[11px] font-medium text-[#9D174D] dark:bg-pink-500/15 dark:text-pink-300">
                                                Kurikulum Merdeka
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom Airtable Highlight Tiles -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-[12px] border border-[var(--at-border)] p-3.5">
                                <p class="text-[11px] font-medium text-[var(--at-muted)]">Metode Presensi</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-[var(--at-fg)]">{{ $pengaturan->metode_absensi ?? 'RFID & GPS' }}</p>
                            </div>
                            <div class="rounded-[12px] border border-[var(--at-border)] p-3.5">
                                <p class="text-[11px] font-medium text-[var(--at-muted)]">Jam Masuk Baku</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums text-[var(--at-fg)]">{{ \Carbon\Carbon::parse($pengaturan->jam_masuk ?? '07:15')->format('H:i') }} WIB</p>
                            </div>
                            <div class="rounded-[12px] border border-[var(--at-border)] p-3.5">
                                <p class="text-[11px] font-medium text-[var(--at-muted)]">Keamanan Akses</p>
                                <p class="mt-1 text-sm font-semibold text-[#006400] dark:text-emerald-400">Role-Isolated</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-[var(--at-border-soft)] px-6 py-4 text-xs text-[var(--at-muted)]">
                    Setiap sesi login, perubahan data akademik, dan ekspor laporan tercatat di log aktivitas sistem.
                </div>
            </aside>
        </main>
    </div>
</body>

</html>
