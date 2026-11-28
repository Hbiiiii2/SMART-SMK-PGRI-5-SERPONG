@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Hub Dapodik &amp; Repository Siswa</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Impor Excel/CSV Dapodik, ekspor repository sekolah, audit kualitas data, dan sinkronisasi WebService API Dapodik.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.siswa.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">Data Siswa</a>
            <a href="{{ route('admin.kelas.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">Master Kelas</a>
            <a href="{{ route('admin.pengaturan-sekolah.edit') }}" class="rounded-lg border border-brand-300 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-500/10 dark:text-brand-400">Pengaturan API Dapodik</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500">Siswa Aktif di Repository</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalActiveStudents }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500">Rata-rata Kelengkapan Data Dapodik</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $avgCompleteness }}%</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500">Status WebService API Dapodik</p>
            <p class="mt-2 text-lg font-semibold {{ $apiConfigured ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $apiConfigured ? 'Terkonfigurasi' : 'Belum Dikonfigurasi' }}
            </p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Card 1: Import Excel / CSV Dapodik --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Impor Unduhan Peserta Didik Dapodik (.xlsx / .csv)</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Mendukung format 2-baris header resmi Dapodik. Email pada CSV disimpan sebagai email kontak/orang tua agar tidak bentrok dengan akun login.</p>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">File Dapodik (.xlsx, .xls, .csv) <span class="text-error-500">*</span></label>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" required
                        class="w-full rounded-lg border border-gray-300 p-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" required class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach($tahunAjarans as $ta)
                                <option value="{{ $ta->id }}" {{ $ta->is_aktif ? 'selected' : '' }}>{{ $ta->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Semester</label>
                        <select name="semester" required class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/40">
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="create_accounts" value="0">
                        <input type="checkbox" name="create_accounts" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500" />
                        Otomatis buatkan akun login portal bagi siswa aktif yang belum punya akun
                    </label>
                    <input type="text" name="default_password" placeholder="Password default opsional (bawaan: NISN/NIPD)"
                        class="mt-2 h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" formaction="{{ route('admin.siswa.dapodik.preview') }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                        Pratinjau &amp; Cek Anomali
                    </button>
                    <button type="submit" formaction="{{ route('admin.siswa.dapodik.import') }}"
                        class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2 text-sm font-medium text-white">
                        Proses Impor ke Repository
                    </button>
                </div>
            </form>
        </div>

        {{-- Card 2: API Sync & Export --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Sinkronisasi WebService API Dapodik (Port 5774)</h3>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Menarik data rombel &amp; peserta didik langsung dari server Dapodik sekolah secara aman tanpa menimpa data lokal SMART+.</p>

                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('admin.siswa.dapodik.test-api') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                            Uji Koneksi API
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.siswa.dapodik.sync-api') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Tarik &amp; Sinkron Sekarang
                        </button>
                    </form>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Ekspor Repository Sekolah (.xlsx)</h3>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Unduh seluruh data siswa terstruktur lengkap (Identitas, Domisili, Ortu/Wali, KIP/PIP, Periodik).</p>
                <form method="GET" action="{{ route('admin.siswa.dapodik.export') }}" class="flex flex-wrap items-center gap-3">
                    <select name="rombel_id" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Semua Kelas</option>
                        @foreach($rombels as $r)
                            <option value="{{ $r->id }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                        Download Excel
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Dry-Run Preview Results --}}
    @if(!empty($previewData))
        <div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50/40 p-6 dark:border-brand-800 dark:bg-brand-950/20">
            <h3 class="mb-2 text-base font-semibold text-gray-800 dark:text-white">
                Hasil Pratinjau: {{ $previewData['filename'] }} ({{ $previewData['format'] }})
            </h3>
            <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                Total baris: <strong>{{ $previewData['total_rows'] }}</strong> ·
                Siswa baru: <strong class="text-emerald-600">{{ $previewData['insert_count'] }}</strong> ·
                Update data ada: <strong class="text-brand-600">{{ $previewData['update_count'] }}</strong> ·
                Dilewati: <strong>{{ $previewData['skipped_count'] }}</strong> ·
                Anomali terdeteksi: <strong class="text-amber-600">{{ $previewData['anomaly_count'] }}</strong>
            </p>
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                            <th class="px-3 py-2">Baris</th>
                            <th class="px-3 py-2">Aksi</th>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">NIPD / NISN</th>
                            <th class="px-3 py-2">Kelas</th>
                            <th class="px-3 py-2">Email Kontak</th>
                            <th class="px-3 py-2">Catatan Anomali</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($previewData['sample_rows'] as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['row_number'] }}</td>
                                <td class="px-3 py-2 font-semibold uppercase">{{ $row['action'] }}</td>
                                <td class="px-3 py-2">{{ $row['nama_lengkap'] }}</td>
                                <td class="px-3 py-2 font-mono">{{ $row['nipd'] ?? '-' }} / {{ $row['nisn'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['kelas'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row['email_pribadi'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-amber-600">{{ !empty($row['anomalies']) ? implode('; ', $row['anomalies']) : 'Bersih' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Data Quality Audit & Sync Logs --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Audit Kualitas Data Dapodik (Perlu Pembenahan)</h3>
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b text-gray-500 dark:border-gray-800">
                            <th class="py-2">Siswa</th>
                            <th class="py-2">Skor</th>
                            <th class="py-2">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($qualityIssues as $qi)
                            <tr>
                                <td class="py-2">
                                    <a href="{{ route('admin.siswa.edit', $qi['siswa']) }}" class="font-semibold text-brand-600 hover:underline">{{ $qi['siswa']->nama_lengkap }}</a>
                                    <div class="text-[11px] text-gray-500">{{ $qi['siswa']->kelas }}</div>
                                </td>
                                <td class="py-2 font-semibold">{{ $qi['score'] }}%</td>
                                <td class="py-2 text-amber-600">{{ implode(', ', array_slice($qi['issues'], 0, 3)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-gray-500">Semua data siswa sudah lengkap 100%.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Impor &amp; Sinkronisasi</h3>
            <div class="max-h-96 space-y-3 overflow-y-auto">
                @forelse($syncLogs as $log)
                    <div class="rounded-xl border border-gray-100 p-3 text-xs dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold uppercase text-gray-800 dark:text-white">{{ $log->source }} · {{ $log->filename ?? 'WebService API' }}</span>
                            <span class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $log->status === 'success' ? 'bg-success-50 text-success-700' : ($log->status === 'partial' ? 'bg-amber-50 text-amber-700' : 'bg-error-50 text-error-700') }}">
                                {{ strtoupper($log->status) }}
                            </span>
                        </div>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $log->summary_notes }}</p>
                        <p class="mt-1 text-[11px] text-gray-400">{{ $log->created_at?->format('d-m-Y H:i') }} · {{ $log->warning_count }} peringatan</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">Belum ada riwayat sinkronisasi Dapodik.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
