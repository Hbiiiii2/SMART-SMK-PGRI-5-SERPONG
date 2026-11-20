<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login Portal Ujian (CBT) | {{ config('app.name', 'SMART CBT') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen bg-slate-50">
            <div class="mx-auto flex min-h-screen max-w-5xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="w-full space-y-4">
                    <!-- Top 3-Portal Switcher -->
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="https://smart.smkpgri5serpong.sch.id/" class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali ke Beranda</span>
                        </a>

                        <nav aria-label="Pilih portal login" class="grid grid-cols-3 gap-1 rounded-xl bg-slate-200/80 p-1 ring-1 ring-inset ring-slate-300/60 sm:inline-flex">
                            <a href="https://smart.smkpgri5serpong.sch.id/login/akademik"
                                class="inline-flex min-h-[38px] items-center justify-center rounded-lg px-3 py-1.5 text-center text-xs font-semibold text-slate-600 hover:text-slate-900">
                                Manajemen Akademik
                            </a>
                            <a href="https://elearning.smkpgri5serpong.sch.id/login"
                                class="inline-flex min-h-[38px] items-center justify-center rounded-lg px-3 py-1.5 text-center text-xs font-semibold text-slate-600 hover:text-slate-900">
                                E-Learning
                            </a>
                            <a href="{{ route('login') }}"
                                class="inline-flex min-h-[38px] items-center justify-center rounded-lg bg-white px-3 py-1.5 text-center text-xs font-semibold text-[#5D2BC2] shadow-sm ring-1 ring-purple-200">
                                Portal Ujian
                            </a>
                        </nav>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.10)]">
                        <div class="h-1.5 w-full bg-[#5D2BC2]"></div>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
