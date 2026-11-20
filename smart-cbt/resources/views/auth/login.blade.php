<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Portal Ujian CBT | {{ config('app.name', 'SMART CBT') }} SMK PGRI 5 Serpong</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* OpenDesign Airtable Design System Tokens disesuaikan untuk Portal Ujian CBT */
            :root {
                --at-bg: #ffffff;
                --at-canvas: #f8fafc;
                --at-surface: #ffffff;
                --at-fg: #181d26;
                --at-fg-2: #333333;
                --at-muted: rgba(4, 14, 32, 0.69);
                --at-border: #e0e2e6;
                --at-border-soft: #eef0f3;
                --at-accent: #181d26;
                --at-accent-hover: #0f172a;
                --at-accent-blue: #1b61c9;
                --at-cbt-amber: #f4b41b;
                --at-cbt-amber-ink: #92400e;
                --at-success: #006400;
                --at-danger: #dc2626;
                --at-font-display: "Haas Groot Disp", "Manrope", -apple-system, system-ui, "Segoe UI", Roboto, sans-serif;
                --at-font-body: "Haas", "Manrope", -apple-system, system-ui, "Segoe UI", Roboto, sans-serif;
                --at-radius-sm: 12px;
                --at-radius-md: 16px;
                --at-radius-pill: 9999px;
                --at-elev-raised:
                    0 0 1px rgba(0, 0, 0, 0.32),
                    0 0 2px rgba(0, 0, 0, 0.08),
                    0 1px 3px rgba(45, 127, 249, 0.28),
                    inset 0 0 0 0.5px rgba(0, 0, 0, 0.06);
                --at-focus-ring: 0 0 0 3px rgba(27, 97, 201, 0.28);
            }

            .cbt-body {
                background-color: var(--at-canvas);
                color: var(--at-fg-2);
                font-family: var(--at-font-body);
                line-height: 1.35;
                letter-spacing: 0.08px;
            }

            .cbt-heading {
                font-family: var(--at-font-display);
                color: var(--at-fg);
                line-height: 1.2;
                letter-spacing: 0;
            }

            .cbt-card {
                background-color: var(--at-surface);
                border: 1px solid var(--at-border);
                border-radius: var(--at-radius-md);
                box-shadow: var(--at-elev-raised);
            }

            .cbt-input {
                min-height: 46px;
                width: 100%;
                border-radius: var(--at-radius-sm);
                border: 1px solid var(--at-border);
                background-color: var(--at-surface);
                color: var(--at-fg);
                font-size: 15px;
                letter-spacing: 0.08px;
                padding: 10px 14px;
                transition: border-color 150ms cubic-bezier(0.2, 0, 0, 1), box-shadow 150ms cubic-bezier(0.2, 0, 0, 1);
            }

            .cbt-input::placeholder {
                color: var(--at-muted);
            }

            .cbt-input:focus-visible {
                outline: none;
                border-color: var(--at-accent-blue);
                box-shadow: var(--at-focus-ring);
            }

            .cbt-btn-primary {
                min-height: 48px;
                border-radius: var(--at-radius-sm);
                background-color: var(--at-accent);
                color: #ffffff;
                font-size: 15px;
                font-weight: 600;
                letter-spacing: 0.08px;
                transition: background-color 150ms cubic-bezier(0.2, 0, 0, 1), transform 150ms cubic-bezier(0.2, 0, 0, 1);
            }

            .cbt-btn-primary:hover {
                background-color: var(--at-accent-hover);
            }

            .cbt-btn-primary:active {
                transform: scale(0.99);
            }

            .cbt-btn-primary:focus-visible {
                outline: none;
                box-shadow: var(--at-focus-ring);
            }
        </style>
    </head>
    <body class="cbt-body min-h-[100dvh] antialiased flex flex-col">

        <!-- Distraction-Free Exam Proctor Status Top Bar (tanpa quick switcher login portal lain) -->
        <header class="bg-[#181d26] text-white border-b border-white/10">
            <div class="mx-auto flex max-w-[1060px] flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-[12px] bg-[#f4b41b] text-[#181d26] shadow-sm">
                        <x-application-logo class="h-6 w-6 fill-current" />
                    </div>
                    <div>
                        <p class="text-sm font-bold tracking-tight text-white">SMART CBT &bull; Portal Ujian</p>
                        <p class="text-[11px] text-slate-300">SMK PGRI 5 Serpong &bull; <span translate="no">cbt.smkpgri5serpong.sch.id</span></p>
                    </div>
                </div>

                <!-- Live Server WIB Clock (Membantu peserta & pengawas memverifikasi waktu mulai sesi ujian) -->
                <div
                    x-data="{
                        nowStr: '{{ now('Asia/Jakarta')->format('H:i:s') }} WIB',
                        init() {
                            const fmt = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false
                            });
                            setInterval(() => {
                                this.nowStr = fmt.format(new Date()).replace(/\./g, ':') + ' WIB';
                            }, 1000);
                        }
                    }"
                    class="flex items-center gap-2.5"
                >
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                        <span>Server Siap</span>
                    </span>
                    <span class="rounded-[10px] border border-white/15 bg-white/10 px-3 py-1 font-mono text-xs font-semibold tabular-nums text-[#f4b41b]" x-text="nowStr">
                        {{ now('Asia/Jakarta')->format('H:i:s') }} WIB
                    </span>
                </div>
            </div>
        </header>

        <!-- Exam Check-In Workspace -->
        <main class="mx-auto flex w-full max-w-[1060px] flex-1 items-center px-4 py-8 sm:px-6">
            <div class="grid w-full grid-cols-1 items-start gap-6 lg:grid-cols-12 lg:gap-8">

                <!-- Left (6 Cols): Dedicated Exam Authentication Form -->
                <section aria-labelledby="cbt-login-heading" class="cbt-card overflow-hidden lg:col-span-6">
                    <div class="h-1.5 w-full bg-[#f4b41b]"></div>

                    <div class="p-6 sm:p-8">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-amber-300/70 bg-amber-50 px-3 py-1 text-xs font-semibold text-[#92400e]">
                                <span class="h-2 w-2 rounded-full bg-[#d97706]" aria-hidden="true"></span>
                                <span>Computer Based Test</span>
                            </span>
                            <span class="text-xs font-medium text-[var(--at-muted)]">Mode Ruang Ujian</span>
                        </div>

                        <h1 id="cbt-login-heading" class="cbt-heading mt-4 text-2xl sm:text-[28px] font-bold">
                            Login Peserta &amp; Pengawas Ujian
                        </h1>

                        <p class="mt-2.5 text-sm leading-[1.45] tracking-[0.1px] text-[var(--at-muted)]">
                            Masuk menggunakan akun peserta ujian (Siswa), Guru pembuat soal, atau Pengawas ruang untuk memulai sesi evaluasi berbasis komputer.
                        </p>

                        <x-auth-session-status class="mt-4" :status="session('status')" />

                        @if ($errors->any())
                            <div role="alert" aria-live="polite" class="mt-4 rounded-[12px] border border-red-200 bg-red-50 p-3.5 text-xs font-medium text-red-700">
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
                            class="mt-6 space-y-4"
                        >
                            @csrf

                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-semibold text-[var(--at-fg)]">
                                    Email Peserta / Pengawas CBT <span class="text-[#dc2626]">*</span>
                                </label>
                                <input
                                    id="email"
                                    class="cbt-input"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="username"
                                    spellcheck="false"
                                    placeholder="peserta.cbt@smkpgri5serpong.sch.id…"
                                />
                            </div>

                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-semibold text-[var(--at-fg)]">
                                    Kata Sandi Ujian <span class="text-[#dc2626]">*</span>
                                </label>
                                <div x-data="{ showPassword: false }" class="relative">
                                    <input
                                        id="password"
                                        class="cbt-input pr-12"
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Masukkan kata sandi akun ujian…"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        class="absolute top-1/2 right-1.5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-[10px] text-[var(--at-muted)] transition-colors duration-150 hover:bg-slate-100 hover:text-[var(--at-fg)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1b61c9]"
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

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                <label for="remember_me" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm font-medium text-[var(--at-fg)] select-none">
                                    <input
                                        id="remember_me"
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        class="h-4 w-4 rounded border-[#e0e2e6] text-[#181d26] focus:ring-[#1b61c9]"
                                    >
                                    <span>Ingat saya</span>
                                </label>

                                @if (Route::has('password.request'))
                                    <a class="inline-flex min-h-[44px] items-center text-sm font-medium text-[#1b61c9] hover:text-[#254fad] hover:underline" href="{{ route('password.request') }}">
                                        Lupa kata sandi?
                                    </a>
                                @endif
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="cbt-btn-primary inline-flex w-full items-center justify-center gap-2 px-6 py-3.5 disabled:opacity-75"
                            >
                                <svg x-show="submitting" style="display: none;" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-85" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Memeriksa kartu & akun…' : 'Masuk ke Ruang Ujian CBT'">Masuk ke Ruang Ujian CBT</span>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Right (6 Cols): Exam Readiness & Proctor Check-In Steps -->
                <aside aria-label="Panduan dan Protokol Ujian CBT" class="space-y-4 lg:col-span-6">
                    <!-- 3-Step Exam Check-In Flow -->
                    <div class="cbt-card p-6">
                        <div class="flex items-center justify-between gap-2 pb-4 border-b border-[var(--at-border-soft)]">
                            <h2 class="cbt-heading text-base font-bold">Prosedur Check-In Sesi Ujian</h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e8f5ec] px-2.5 py-0.5 text-[11px] font-semibold text-[#006400]">
                                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                STS / SAS
                            </span>
                        </div>

                        <ol class="mt-4 space-y-3.5">
                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[8px] bg-[#181d26] font-mono text-xs font-bold tabular-nums text-[#f4b41b]">
                                    01
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-[var(--at-fg)]">Masuk Dengan Akun Peserta</p>
                                    <p class="mt-0.5 text-xs text-[var(--at-muted)]">Pastikan email dan kata sandi sesuai kartu peserta ujian yang diterbitkan panitia.</p>
                                </div>
                            </li>

                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[8px] bg-amber-100 font-mono text-xs font-bold tabular-nums text-[#92400e]">
                                    02
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-[var(--at-fg)]">Masukkan Token Ujian Aktif</p>
                                    <p class="mt-0.5 text-xs text-[var(--at-muted)]">Token ujian dirotasi berkala dan dibagikan langsung oleh pengawas ruangan sebelum mulai.</p>
                                </div>
                            </li>

                            <li class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[8px] bg-blue-50 font-mono text-xs font-bold tabular-nums text-[#1b61c9]">
                                    03
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-[var(--at-fg)]">Simpan Jawaban Otomatis (Auto-Save)</p>
                                    <p class="mt-0.5 text-xs text-[var(--at-muted)]">Setiap pilihan jawaban tersimpan berkala sehingga aman apabila koneksi terputus sementara.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Exam Specifications Matrix (Airtable Bento Grid) -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="cbt-card p-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22px] text-[var(--at-muted)]">Tipe Evaluasi</p>
                            <p class="mt-1 text-sm font-semibold text-[var(--at-fg)]">Pilihan Ganda &amp; Esai</p>
                            <p class="mt-1 text-xs text-[var(--at-muted)]">Mendukung gambar soal &amp; persamaan matematika.</p>
                        </div>

                        <div class="cbt-card p-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22px] text-[var(--at-muted)]">Pengawasan</p>
                            <p class="mt-1 text-sm font-semibold text-[var(--at-fg)]">Token &amp; Monitoring Live</p>
                            <p class="mt-1 text-xs text-[var(--at-muted)]">Status pengerjaan dipantau real-time oleh guru &amp; pengawas.</p>
                        </div>
                    </div>
                </aside>

            </div>
        </main>

        <footer class="border-t border-[var(--at-border-soft)] bg-white py-4 text-center text-xs text-[var(--at-muted)]">
            SMART CBT &bull; Portal Evaluasi SMK PGRI 5 Serpong &bull; <span class="font-semibold text-[var(--at-fg)]" translate="no">Aybisa Studio</span>
        </footer>
    </body>
</html>
