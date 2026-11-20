<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SMART CBT') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --at-bg: #ffffff;
                --at-canvas: #f8fafc;
                --at-fg: #181d26;
                --at-fg-2: #333333;
                --at-muted: rgba(4, 14, 32, 0.69);
                --at-border: #e0e2e6;
                --at-accent: #1b61c9;
                --at-elev-raised:
                    0 0 1px rgba(0, 0, 0, 0.32),
                    0 0 2px rgba(0, 0, 0, 0.08),
                    0 1px 3px rgba(45, 127, 249, 0.28),
                    inset 0 0 0 0.5px rgba(0, 0, 0, 0.06);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#f8fafc] text-[#181d26]">
        <div class="flex min-h-[100dvh] flex-col items-center justify-center px-4 py-8 sm:px-6">
            <div class="mb-6 flex items-center gap-3">
                <a href="{{ route('login') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-[12px] bg-[#181d26] text-[#f4b41b] shadow-sm">
                        <x-application-logo class="h-6 w-6 fill-current" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-[#181d26]">SMART CBT</p>
                        <p class="text-xs text-[rgba(4,14,32,0.69)]">Portal Evaluasi SMK PGRI 5 Serpong</p>
                    </div>
                </a>
            </div>

            <div class="w-full max-w-md overflow-hidden rounded-[16px] border border-[#e0e2e6] bg-white px-6 py-6" style="box-shadow: var(--at-elev-raised);">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
