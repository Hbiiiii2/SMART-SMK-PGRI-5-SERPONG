@extends('layouts.app')

@section('content')
@php
    $comp = $siswa->data_completeness;
    $p = $siswa->profil;
    $ayah = $siswa->orangTuas->firstWhere('hubungan', 'ayah');
    $ibu = $siswa->orangTuas->firstWhere('hubungan', 'ibu');
    $wali = $siswa->orangTuas->firstWhere('hubungan', 'wali');
@endphp
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $siswa->nama_lengkap }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Rombel: <strong>{{ $siswa->rombel?->nama ?? $siswa->kelas ?? '-' }}</strong> ·
                NIPD: <span class="font-mono">{{ $siswa->nipd ?? '-' }}</span> ·
                NISN: <span class="font-mono">{{ $siswa->nisn ?? '-' }}</span> ·
                Status: <strong>{{ $siswa->status_label }}</strong>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.siswa.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">Kembali</a>
            <a href="{{ route('erapor.buku-induk.show', $siswa) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">Buku Induk &amp; Nilai</a>
            <a href="{{ route('admin.siswa.edit', $siswa) }}" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2 text-sm font-medium text-white">Edit Profil</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Identitas Utama --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-2">
            <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Identitas Pokok Peserta Didik</h3>
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div><span class="text-xs text-gray-500">NIK</span><p class="font-mono font-medium text-gray-800 dark:text-white">{{ $siswa->nik ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Nomor KK</span><p class="font-mono font-medium text-gray-800 dark:text-white">{{ $siswa->no_kk ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Tempat, Tanggal Lahir</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->tempat_lahir ?? '-' }}, {{ $siswa->tanggal_lahir?->format('d-m-Y') ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Jenis Kelamin / Agama</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '-') }} / {{ $siswa->agama ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">No. HP (Dapodik) / WA Aktif</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->no_telepon ?? '-' }} / {{ $siswa->no_wa_aktif ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Email Kontak / Ortu (Dapodik)</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->email_pribadi ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-500">Email Akun Login SMART+</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->user?->email ?? 'Belum dibuat' }}</p></div>
                <div><span class="text-xs text-gray-500">Wali Kelas Rombel</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->rombel?->waliKelas?->nama_lengkap ?? '-' }}</p></div>
                <div class="sm:col-span-2"><span class="text-xs text-gray-500">Alamat Domisili</span><p class="font-medium text-gray-800 dark:text-white">{{ $siswa->alamat ?? $p?->alamat_jalan ?? '-' }}</p></div>
            </div>
        </div>

        {{-- Kelengkapan Data Dapodik --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Skor Kelengkapan Dapodik</h3>
            <div class="my-4 flex items-baseline gap-2">
                <span class="text-4xl font-bold {{ $comp['score'] >= 90 ? 'text-emerald-600' : ($comp['score'] >= 60 ? 'text-amber-600' : 'text-error-600') }}">{{ $comp['score'] }}%</span>
                <span class="text-xs text-gray-500">({{ $comp['filled'] }} / {{ $comp['total'] }} indikator)</span>
            </div>
            @if(!empty($comp['issues']))
                <p class="mb-2 text-xs font-semibold text-amber-600">Perlu dilengkapi:</p>
                <ul class="list-disc space-y-1 pl-4 text-xs text-gray-600 dark:text-gray-400">
                    @foreach($comp['issues'] as $iss)
                        <li>{{ $iss }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-emerald-600">Seluruh indikator pokok Dapodik sudah lengkap.</p>
            @endif
            <div class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800">
                Sumber sinkron: <strong>{{ $siswa->sync_source ?? 'manual' }}</strong><br>
                Terakhir sync: {{ $siswa->last_synced_at?->format('d-m-Y H:i') ?? '-' }}
            </div>
        </div>
    </div>

    {{-- Orang Tua & Wali --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        @foreach(['Ayah Kandung' => $ayah, 'Ibu Kandung' => $ibu, 'Wali' => $wali] as $label => $row)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $label }}</h4>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Nama</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $row?->nama ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">NIK</dt><dd class="font-mono text-gray-800 dark:text-white">{{ $row?->nik ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Tahun Lahir</dt><dd class="text-gray-800 dark:text-white">{{ $row?->tahun_lahir ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Pendidikan</dt><dd class="text-gray-800 dark:text-white">{{ $row?->jenjang_pendidikan ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Pekerjaan</dt><dd class="text-gray-800 dark:text-white">{{ $row?->pekerjaan ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Penghasilan</dt><dd class="text-gray-800 dark:text-white">{{ $row?->penghasilan ?? '-' }}</dd></div>
                </dl>
            </div>
        @endforeach
    </div>

    {{-- Dokumen, Bantuan & Periodik --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Dokumen, Kesejahteraan &amp; Bank</h3>
            <dl class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                <div><dt class="text-gray-500">Sekolah Asal</dt><dd class="font-medium text-gray-800 dark:text-white">{{ $p?->sekolah_asal ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">No. Akta Lahir</dt><dd class="font-mono text-gray-800 dark:text-white">{{ $p?->no_registrasi_akta_lahir ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">KPS / KKS</dt><dd class="text-gray-800 dark:text-white">{{ $p?->penerima_kps ? 'Ya (' . ($p?->no_kps ?? '-') . ')' : 'Tidak' }} / {{ $p?->nomor_kks ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">KIP</dt><dd class="text-gray-800 dark:text-white">{{ $p?->penerima_kip ? 'Ya (' . ($p?->nomor_kip ?? '-') . ')' : 'Tidak' }}</dd></div>
                <div><dt class="text-gray-500">Layak PIP</dt><dd class="text-gray-800 dark:text-white">{{ $p?->layak_pip ? 'Ya - ' . ($p?->alasan_layak_pip ?? '') : 'Tidak' }}</dd></div>
                <div><dt class="text-gray-500">Bank / Rekening</dt><dd class="text-gray-800 dark:text-white">{{ $p?->bank ? "{$p->bank} - {$p->nomor_rekening_bank} ({$p->rekening_atas_nama})" : '-' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Periodik &amp; Rombel</h3>
            @forelse($siswa->periodiks as $per)
                <div class="mb-2 rounded-lg border border-gray-100 p-3 text-xs dark:border-gray-800">
                    <strong>{{ $per->tahunAjaran?->nama ?? '-' }} ({{ $per->semester }})</strong>:
                    BB {{ $per->berat_badan ?? '-' }} kg · TB {{ $per->tinggi_badan ?? '-' }} cm ·
                    Saudara {{ $per->jumlah_saudara_kandung ?? '-' }} · Jarak {{ $per->jarak_rumah_km ?? '-' }} km
                </div>
            @empty
                <p class="text-xs text-gray-500">Belum ada data periodik.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
