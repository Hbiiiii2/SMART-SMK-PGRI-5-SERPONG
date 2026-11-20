@php
    try {
        $pengaturan = \App\Models\PengaturanSekolah::first();
    } catch (\Throwable $e) {
        $pengaturan = null;
    }
    $namaSekolah = $pengaturan->nama_sekolah ?? 'SMK PGRI 5 Serpong';
    $tanggalHariIni = \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Portal E-Learning &amp; LMS | SMART+ {{ $namaSekolah }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('pages.auth.partials.airtable-tokens')

    <style>
        /* Override accent cleanly ke Forest Emerald Airtable (--success #006400 family) agar kontras dan berbeda jelas dari Portal Admin Akademik */
        .elearning-scope {
            --at-accent: #0b6e35;
            --at-accent-hover: #005425;
            --at-accent-active: #00421c;
            --at-focus-ring: 0 0 0 3px rgba(11, 110, 53, 0.28);
        }
    </style>

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

<body class="at-body elearning-scope min-h-[100dvh] antialiased flex flex-col">

    <!-- Minimal Learning Top Workspace Strip (tanpa quick switcher antar login portal) -->
    <header class="border-b border-[var(--at-border-soft)] bg-[var(--at-surface)]">
        <div class="mx-auto flex max-w-[1040px] flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-6">
            <div class="flex items-center gap-3 min-w-0">
                @if(!empty($pengaturan->logo))
                    <img src="{{ asset('storage/' . $pengaturan->logo) }}" width="36" height="36" alt="Logo {{ $namaSekolah }}" class="h-9 w-9 shrink-0 rounded-[10px] object-cover ring-1 ring-[var(--at-border)]">
                @else
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#0b6e35] text-sm font-bold text-white">
                        E+
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-[var(--at-fg)]">SMART+ E-Learning &amp; LMS</p>
                    <p class="truncate text-[11px] text-[var(--at-muted)]">{{ $namaSekolah }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--at-border)] bg-[var(--at-surface)] px-3 py-1 text-xs font-medium text-[var(--at-fg)]">
                    <span class="h-2 w-2 rounded-full bg-[#16a34a]" aria-hidden="true"></span>
                    <time datetime="{{ now('Asia/Jakarta')->toDateString() }}">{{ $tanggalHariIni }}</time>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Centered Learning Stage + Dual Siswa & Guru Role Bento Strip -->
    <main class="mx-auto flex w-full max-w-[960px] flex-1 flex-col justify-center px-4 py-8 sm:px-6 sm:py-10">

        <!-- Hero Headline (Centered, Airy Swiss Hierarchy) -->
        <div class="mx-auto max-w-2xl text-center">
            <div class="inline-flex flex-wrap items-center justify-center gap-2">
                <span class="at-base-chip">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#16a34a]" aria-hidden="true"></span>
                    <span>Ruang Belajar Siswa</span>
                </span>
                <span class="at-base-chip">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#1b61c9]" aria-hidden="true"></span>
                    <span>Ruang Mengajar Guru</span>
                </span>
            </div>

            <h1 class="at-heading mt-4 text-3xl sm:text-4xl font-semibold">
                Masuk ke Portal E-Learning
            </h1>

            <p class="mx-auto mt-3 max-w-xl text-[15px] leading-[1.45] tracking-[0.12px] text-[var(--at-muted)]">
                Satu pintu pembelajaran digital untuk mengakses materi ajar, mengumpulkan tugas kelas, melihat jadwal KBM harian, serta presensi belajar siswa dan guru.
            </p>
        </div>

        <!-- Centered Focal Airtable Authentication Card -->
        <section aria-label="Form Masuk E-Learning Siswa dan Guru" class="at-card mx-auto mt-7 w-full max-w-[460px] overflow-hidden rounded-[24px]">
            <div class="h-1.5 w-full bg-[#0b6e35]"></div>

            <div class="p-6 sm:p-8">
                @if ($errors->any())
                    <div role="alert" aria-live="polite" class="mb-5 rounded-[12px] border border-[#dc2626]/30 bg-red-50 p-3.5 text-xs font-medium text-[#b91c1c] dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('login') }}"
                    x-data="{ submitting: false }"
                    @submit="submitting = true"
                    class="space-y-4"
                >
                    @csrf
                    <input type="hidden" name="portal" value="elearning">

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium tracking-[0.07px] text-[var(--at-fg)]">
                            Email Akun Siswa / Guru <span class="text-[#dc2626]">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            spellcheck="false"
                            placeholder="siswa.atau.guru@smkpgri5serpong.sch.id…"
                            class="at-input"
                        >
                        <p class="mt-1.5 text-xs tracking-[0.07px] text-[var(--at-muted)]">
                            Masuk sesuai peran Anda; sistem otomatis membuka dashboard Siswa atau Guru.
                        </p>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium tracking-[0.07px] text-[var(--at-fg)]">
                            Password <span class="text-[#dc2626]">*</span>
                        </label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password Anda…"
                                class="at-input pr-12"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                class="absolute top-1/2 right-1.5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-[10px] text-[var(--at-muted)] transition-colors duration-150 hover:bg-slate-100 hover:text-[var(--at-fg)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0b6e35] dark:hover:bg-slate-800"
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

                    <div class="flex items-center justify-between pt-1">
                        <label for="remember" x-data="{ checked: {{ old('remember') ? 'true' : 'false' }} }" class="relative inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm font-medium text-[var(--at-fg)] select-none">
                            <input type="checkbox" id="remember" name="remember" value="1" x-model="checked" {{ old('remember') ? 'checked' : '' }} class="peer sr-only">
                            <span
                                :class="checked ? 'border-[#0b6e35] bg-[#0b6e35] text-white' : 'border-[var(--at-border)] bg-[var(--at-surface)] text-transparent'"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border transition-colors duration-150 peer-checked:border-[#0b6e35] peer-checked:bg-[#0b6e35] peer-checked:text-white peer-checked:[&>svg]:opacity-100 peer-focus-visible:ring-2 peer-focus-visible:ring-[#0b6e35]/40"
                            >
                                <svg :class="checked ? 'opacity-100' : 'opacity-0'" class="h-3.5 w-3.5 transition-opacity duration-150" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>Ingat saya</span>
                        </label>

                        <span class="font-mono text-[11px] text-[var(--at-muted)]" translate="no">elearning</span>
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
                        <span x-text="submitting ? 'Membuka ruang belajar…' : 'Masuk ke Ruang E-Learning'">Masuk ke Ruang E-Learning</span>
                    </button>
                </form>
            </div>
        </section>

        <!-- Dual Role Capability Cards (Siswa & Guru LMS Flow) -->
        <section aria-label="Fitur Portal Siswa dan Guru" class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
            <article class="at-card p-5 transition-transform duration-200 hover:-translate-y-0.5">
                <div class="flex items-start gap-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-[#E8F5EC] text-[#006400] dark:bg-emerald-500/15 dark:text-emerald-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="at-heading text-base font-semibold">Workspace Siswa</h2>
                        <p class="mt-1.5 text-xs leading-[1.45] text-[var(--at-muted)]">
                            Akses jadwal mata pelajaran harian, baca &amp; unduh materi pembelajaran (PDF &amp; video), kumpulkan tugas sebelum tenggat, dan catat presensi GPS.
                        </p>
                    </div>
                </div>
            </article>

            <article class="at-card p-5 transition-transform duration-200 hover:-translate-y-0.5">
                <div class="flex items-start gap-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-[#EBF3FF] text-[#1b61c9] dark:bg-blue-500/15 dark:text-blue-300" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="at-heading text-base font-semibold">Workspace Guru Pengampu</h2>
                        <p class="mt-1.5 text-xs leading-[1.45] text-[var(--at-muted)]">
                            Publikasikan modul bahan ajar, buat &amp; nilai tugas siswa per kelas, pantau rekap kehadiran jam pelajaran, serta presensi GPS mandiri guru.
                        </p>
                    </div>
                </div>
            </article>
        </section>
    </main>

    <footer class="border-t border-[var(--at-border-soft)] py-4 text-center text-xs text-[var(--at-muted)]">
        SMART+ E-Learning &bull; {{ $namaSekolah }} &bull; Powered by <span class="font-semibold text-[#0b6e35] dark:text-emerald-400" translate="no">Aybisa Studio</span>
    </footer>
</body>

</html>
