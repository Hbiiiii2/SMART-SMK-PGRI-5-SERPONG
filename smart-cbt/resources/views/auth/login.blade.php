<x-guest-layout>
    <div class="grid grid-cols-1 lg:grid-cols-12">
        <!-- Left Context Panel: Computer Based Test -->
        <div class="flex flex-col justify-between border-b border-purple-200/70 bg-[#F1E9FF]/60 p-6 sm:p-8 lg:col-span-5 lg:border-r lg:border-b-0">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#5D2BC2] text-white shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <span class="inline-flex rounded-md bg-[#F1E9FF] px-2.5 py-1 text-xs font-semibold text-[#5D2BC2] ring-1 ring-inset ring-[#5D2BC2]/20">
                        Computer Based Test &amp; Evaluasi Akademik
                    </span>
                </div>

                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">
                    Portal Ujian Berbasis Komputer &mdash; SMK PGRI 5 Serpong
                </h1>

                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    Pelaksanaan ujian sekolah, penilaian tengah semester (STS), penilaian akhir semester (SAS), dan evaluasi kompetensi berbasis komputer dengan pengawasan token ujian real-time.
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-[#5D2BC2] ring-1 ring-inset ring-purple-200">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        <span>Ujian STS &amp; SAS</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-[#5D2BC2] ring-1 ring-inset ring-purple-200">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        <span>Validasi Token Ujian</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-[#5D2BC2] ring-1 ring-inset ring-purple-200">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        <span>Evaluasi Terintegrasi</span>
                    </span>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-purple-200/80 bg-white/90 p-3.5 text-xs">
                <p class="font-semibold text-slate-500">Pengguna Portal Ini</p>
                <p class="mt-1 font-bold text-slate-900">Peserta Ujian (Siswa), Guru Pembuat Soal &amp; Pengawas CBT</p>
            </div>
        </div>

        <!-- Right Authentication Form Column -->
        <div class="p-6 sm:p-8 lg:col-span-7 lg:p-10">
            <div class="mb-6 flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#5D2BC2] text-white shadow-sm">
                    <x-application-logo class="h-7 w-7 fill-current text-white" />
                </div>
                <div>
                    <p class="text-base font-bold text-slate-900">SMART CBT</p>
                    <p class="text-xs text-slate-500">Sistem Ujian Online Terintegrasi SMK PGRI 5 Serpong</p>
                </div>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email Peserta / Pengawas Ujian')" />
                    <x-text-input id="email" class="mt-1.5 block w-full rounded-xl" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Masukkan email akun peserta atau guru CBT" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="mt-1.5 block w-full rounded-xl"
                                    type="password"
                                    name="password"
                                    required autocomplete="current-password"
                                    placeholder="Masukkan password Anda" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between gap-3">
                    <label for="remember_me" class="inline-flex cursor-pointer items-center select-none">
                        <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#5D2BC2] shadow-sm focus:ring-[#5D2BC2]" name="remember" value="1">
                        <span class="ms-2 text-sm font-medium text-slate-700">Ingat saya</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm font-medium text-[#5D2BC2] hover:underline" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>

                <button type="submit" class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#5D2BC2] px-4 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#4A1FA3] focus:outline-none focus:ring-2 focus:ring-[#5D2BC2] focus:ring-offset-2">
                    Masuk ke Portal Ujian
                </button>
            </form>

            <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">
                Terhubung langsung dengan portal <a href="https://smart.smkpgri5serpong.sch.id/" class="font-semibold text-[#5D2BC2] hover:underline">SMART+ SMK PGRI 5 Serpong</a>
            </div>
        </div>
    </div>
</x-guest-layout>
