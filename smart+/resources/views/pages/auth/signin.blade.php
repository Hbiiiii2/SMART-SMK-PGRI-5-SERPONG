@extends('layouts.auth')

@section('content')
@php
    $portal = $portalContext ?? [
        'key' => 'akademik',
        'title' => 'Login Manajemen Akademik',
        'badge' => 'Portal Manajemen Akademik & Presensi',
        'heading' => 'Manajemen Akademik & Presensi',
        'description' => 'Pusat kendali presensi RFID & GPS Geofencing, penjadwalan akademik, pengelolaan E-Rapor Kurikulum Merdeka, serta pemantauan kehadiran guru, staf, dan siswa secara real-time.',
        'email_label' => 'Email Akun Akademik',
        'email_placeholder' => 'Masukkan email guru, staf, atau admin',
        'button_text' => 'Masuk ke Portal Akademik',
        'audience' => 'Admin Sekolah, Kepala Sekolah, Guru & Staf Akademik',
        'highlights' => ['Presensi RFID & GPS', 'E-Rapor Terintegrasi', 'Monitoring Kehadiran'],
    ];

    $key = $portal['key'] ?? 'akademik';

    $theme = match ($key) {
        'elearning' => [
            'topBar' => 'bg-emerald-600',
            'panelBg' => 'bg-[#E4FBE7]/55 border-emerald-200/70 dark:bg-emerald-950/35 dark:border-emerald-800/40',
            'badgeClass' => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-700/15 dark:bg-emerald-500/20 dark:text-emerald-300',
            'iconWrap' => 'bg-emerald-600 text-white shadow-sm',
            'chipClass' => 'bg-white text-emerald-800 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-700/50',
            'btnClass' => 'bg-emerald-600 hover:bg-emerald-700 focus-visible:outline-emerald-600',
            'tabActive' => 'bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-200/80 dark:bg-gray-900 dark:text-emerald-300 dark:ring-emerald-700',
            'audienceBg' => 'bg-white/90 border-emerald-200/80 dark:bg-gray-900/70 dark:border-emerald-800/40',
        ],
        'ujian' => [
            'topBar' => 'bg-[#5D2BC2]',
            'panelBg' => 'bg-[#F1E9FF]/60 border-purple-200/70 dark:bg-purple-950/35 dark:border-purple-800/40',
            'badgeClass' => 'bg-[#F1E9FF] text-[#5D2BC2] ring-1 ring-inset ring-[#5D2BC2]/20 dark:bg-[#8B46FF]/20 dark:text-[#C5A3FF]',
            'iconWrap' => 'bg-[#5D2BC2] text-white shadow-sm',
            'chipClass' => 'bg-white text-[#5D2BC2] ring-1 ring-inset ring-purple-200 dark:bg-purple-900/40 dark:text-purple-200 dark:ring-purple-700/50',
            'btnClass' => 'bg-[#5D2BC2] hover:bg-[#4A1FA3] focus-visible:outline-[#5D2BC2]',
            'tabActive' => 'bg-white text-[#5D2BC2] shadow-sm ring-1 ring-purple-200/80 dark:bg-gray-900 dark:text-[#C5A3FF] dark:ring-purple-700',
            'audienceBg' => 'bg-white/90 border-purple-200/80 dark:bg-gray-900/70 dark:border-purple-800/40',
        ],
        default => [
            'topBar' => 'bg-[#166EE1]',
            'panelBg' => 'bg-[#EBF3FF]/65 border-blue-200/70 dark:bg-blue-950/35 dark:border-blue-800/40',
            'badgeClass' => 'bg-[#EBF3FF] text-[#0D52AC] ring-1 ring-inset ring-[#166EE1]/20 dark:bg-[#166EE1]/20 dark:text-[#66A8FF]',
            'iconWrap' => 'bg-[#166EE1] text-white shadow-sm',
            'chipClass' => 'bg-white text-[#0D52AC] ring-1 ring-inset ring-blue-200 dark:bg-blue-900/40 dark:text-blue-200 dark:ring-blue-700/50',
            'btnClass' => 'bg-[#166EE1] hover:bg-[#0D52AC] focus-visible:outline-[#166EE1]',
            'tabActive' => 'bg-white text-[#166EE1] shadow-sm ring-1 ring-blue-200/80 dark:bg-gray-900 dark:text-[#4EA8FF] dark:ring-blue-700',
            'audienceBg' => 'bg-white/90 border-blue-200/80 dark:bg-gray-900/70 dark:border-blue-800/40',
        ],
    };

    $logoSekolah = \App\Models\PengaturanSekolah::first();
@endphp

<div class="space-y-4">
    <!-- Top 3-Portal Quick Switcher Bar & Beranda Link -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-2xs transition-colors duration-150 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>

        <nav aria-label="Pilih portal login" class="grid grid-cols-3 gap-1 rounded-xl bg-gray-200/75 p-1 ring-1 ring-inset ring-gray-300/60 sm:inline-flex dark:bg-gray-800/90 dark:ring-gray-700">
            <a href="{{ route('login.akademik') }}"
                class="inline-flex min-h-[38px] items-center justify-center rounded-lg px-3 py-1.5 text-center text-xs font-semibold transition-colors duration-150 {{ $key === 'akademik' ? $theme['tabActive'] : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                Manajemen Akademik
            </a>
            <a href="{{ route('login.elearning') }}"
                class="inline-flex min-h-[38px] items-center justify-center rounded-lg px-3 py-1.5 text-center text-xs font-semibold transition-colors duration-150 {{ $key === 'elearning' ? $theme['tabActive'] : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                E-Learning
            </a>
            <a href="{{ route('login.ujian') }}"
                class="inline-flex min-h-[38px] items-center justify-center rounded-lg px-3 py-1.5 text-center text-xs font-semibold transition-colors duration-150 {{ $key === 'ujian' ? $theme['tabActive'] : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                Portal Ujian
            </a>
        </nav>
    </div>

    <!-- Split Context Card -->
    <div class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-[0_4px_20px_rgba(16,24,40,0.08)] dark:border-gray-800 dark:bg-gray-900">
        <div class="h-1.5 w-full {{ $theme['topBar'] }}"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12">
            <!-- Left Portal Context Column -->
            <div class="flex flex-col justify-between border-b p-6 sm:p-8 lg:col-span-5 lg:border-r lg:border-b-0 {{ $theme['panelBg'] }}">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $theme['iconWrap'] }}">
                            @if($key === 'elearning')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            @elseif($key === 'ujian')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            @endif
                        </div>
                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $theme['badgeClass'] }}">
                            {{ $portal['badge'] }}
                        </span>
                    </div>

                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $portal['heading'] }}
                    </h1>

                    <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ $portal['description'] }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach($portal['highlights'] as $item)
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold {{ $theme['chipClass'] }}">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $item }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="rounded-xl border p-3.5 text-xs {{ $theme['audienceBg'] }}">
                        <p class="font-semibold text-gray-500 dark:text-gray-400">Pengguna Portal Ini</p>
                        <p class="mt-1 font-bold text-gray-900 dark:text-white">{{ $portal['audience'] }}</p>
                    </div>

                    @if($key === 'ujian')
                        <a href="https://cbt.smkpgri5serpong.sch.id/login" target="_blank" rel="noopener noreferrer"
                            class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-xl border border-[#5D2BC2]/30 bg-white px-4 py-2 text-xs font-bold text-[#5D2BC2] shadow-2xs transition-colors duration-150 hover:bg-[#F1E9FF] dark:border-purple-700 dark:bg-gray-900 dark:text-purple-300">
                            <span>Buka Server Ujian CBT Utama</span>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Right Authentication Form Column -->
            <div class="p-6 sm:p-8 lg:col-span-7 lg:p-10">
                <div class="mb-6 flex items-center gap-3.5">
                    @if(!empty($logoSekolah->logo))
                        <img src="{{ asset('storage/' . $logoSekolah->logo) }}" alt="Logo Sekolah" class="h-12 w-12 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold {{ $theme['iconWrap'] }}">S+</div>
                    @endif
                    <div>
                        <p class="text-base font-bold text-gray-900 dark:text-white">{{ $logoSekolah->nama_sekolah ?? 'SMART+' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">School Management Attendance &amp; Reporting Technologies</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-800/50 dark:bg-rose-500/10 dark:text-rose-400">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ $portal['email_label'] }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                            placeholder="{{ $portal['email_placeholder'] }}"
                            class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#166EE1] focus:ring-2 focus:ring-[#166EE1]/20 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Password <span class="text-rose-500">*</span>
                        </label>
                        <div x-data="{ showPassword: false }" class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                                placeholder="Masukkan password Anda"
                                class="h-11 w-full rounded-xl border border-gray-300 bg-white py-2.5 pr-11 pl-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#166EE1] focus:ring-2 focus:ring-[#166EE1]/20 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500" />
                            <button type="button" @click="showPassword = !showPassword" aria-label="Tampilkan atau sembunyikan password"
                                class="absolute top-1/2 right-3 -translate-y-1/2 rounded-lg p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg x-show="!showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="currentColor" />
                                </svg>
                                <svg x-show="showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display: none;">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="currentColor" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember" x-data="{ checked: {{ old('remember') ? 'true' : 'false' }} }" class="relative flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-300">
                            <input type="checkbox" id="remember" name="remember" value="1" x-model="checked" {{ old('remember') ? 'checked' : '' }} class="peer sr-only" />
                            <div :class="checked ? 'border-brand-500 bg-brand-500 text-white' : 'bg-transparent border-gray-300 text-transparent dark:border-gray-700'"
                                class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] transition-colors duration-150 peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-white peer-checked:[&>span]:opacity-100 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/30">
                                <span :class="checked ? 'opacity-100' : 'opacity-0'" class="transition-opacity duration-150">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                        <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="currentColor" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </div>
                            Ingat saya
                        </label>
                    </div>

                    <div>
                        <button type="submit"
                            class="flex min-h-[46px] w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 {{ $theme['btnClass'] }}">
                            {{ $portal['button_text'] }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 border-t border-gray-100 pt-5 text-center text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    Powered by <span class="font-semibold text-[#166EE1] dark:text-[#4EA8FF]">Aybisa Studio</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
