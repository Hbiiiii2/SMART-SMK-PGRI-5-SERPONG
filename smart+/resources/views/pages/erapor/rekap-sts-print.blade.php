<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $paperSize = $raporSettings?->ukuran_kertas ?? 'A4';
        $cssPageSize = match($paperSize) {
            'F4' => '330mm 215mm',
            'Letter' => 'letter landscape',
            default => 'A4 landscape',
        };

        $resolveImgDataUri = static function (?string $storagePath, string $fallbackPublicRelPath): ?string {
            $candidates = [];
            if (!empty($storagePath)) {
                $raw = ltrim($storagePath, '/');
                $candidates[] = storage_path('app/public/' . $raw);
                $candidates[] = public_path('storage/' . $raw);
                $candidates[] = public_path($raw);
            }
            $candidates[] = public_path($fallbackPublicRelPath);

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $mime = @mime_content_type($candidate) ?: 'image/png';
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
                }
            }
            return null;
        };

        $logoLeft = $resolveImgDataUri($raporSettings?->logo_yayasan, 'images/rapor/logo-pgri.png');
        $logoRight = $resolveImgDataUri($schoolSettings?->logo, 'images/rapor/logo-smk-pgri5.png');

        $schoolName = $schoolSettings?->nama_sekolah ?: 'SMK PGRI 5 Serpong';
        $headerYayasan = $raporSettings?->header_yayasan ?: 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP)';
        $headerSekolah = $raporSettings?->header_sekolah ?: strtoupper($schoolName);
        $alamatSekolah = $schoolSettings?->alamat_sekolah ?: 'Jl. Raya Puspiptek (Depan Masjid Agung) Serpong - Tangerang Selatan';
        $tahunNama = $selectedYear?->nama ?? '-';

        $waliNama = $homeroom?->effective_nama ?? $waliKelasGuru?->nama_lengkap ?? 'Wali Kelas';
        $waliNip = $homeroom?->effective_nip ?? $waliKelasGuru?->nip ?? '-';
        $kepsekNama = $raporSettings?->nama_kepala_sekolah ?? $kepalaSekolah?->name ?? 'Kepala Sekolah';
        $kepsekNip = $raporSettings?->nip_kepala_sekolah ?: ($kepalaSekolah?->guru?->nip ?? '-');
        $kotaPembagian = $raporSettings?->kota_pembagian ?: 'Kota Tangerang Selatan';
    @endphp
    <title>Leger Raport Kelas {{ $selectedClass }} — {{ $semester }} ({{ $tahunNama }})</title>
    <style>
        @page {
            size: {{ $cssPageSize }};
            margin: 8mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 8pt;
            line-height: 1.25;
        }

        .no-print {
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .no-print button {
            background: #e11d48;
            color: #fff;
            border: none;
            padding: 7px 14px;
            border-radius: 7px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .no-print button:hover {
            background: #be123c;
        }

        .sheet {
            width: 100%;
            max-width: 1120px;
            margin: 14px auto 24px auto;
            background: #fff;
            padding: 14px 18px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
            box-sizing: border-box;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            width: 76px;
            text-align: center;
        }
        .kop-logo img {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }
        .kop-center {
            text-align: center;
            line-height: 1.22;
        }
        .kop-yayasan {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kop-sekolah {
            font-size: 13pt;
            font-weight: bold;
            color: #0033cc;
            text-transform: uppercase;
            margin: 1px 0;
        }
        .kop-alamat {
            font-size: 8pt;
            color: #222;
        }
        .kop-judul {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4px;
            letter-spacing: 0.4px;
        }
        .kop-divider {
            border-top: 1.5px solid #000;
            margin: 5px 0 8px 0;
        }

        /* Meta info */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .meta-table td {
            padding: 1.5px 4px 1.5px 0;
            vertical-align: top;
        }

        /* Leger Matrix */
        .leger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2pt;
            table-layout: fixed;
        }
        .leger-table thead {
            display: table-header-group;
        }
        .leger-table th,
        .leger-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .leger-table th {
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .leger-subhead {
            font-size: 6.5pt;
            line-height: 1.15;
            padding: 4px 2px !important;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .bg-summary { background-color: #f3f4f6; }
        .bg-stat { background-color: #f8fafc; font-weight: bold; }

        .footer-keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 8.5pt;
        }
        .sig-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .sig-space {
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .sheet {
                margin: 0;
                padding: 0;
                max-width: 100%;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <div>
        <strong>LEGER RAPORT — {{ $headerSekolah }}</strong>
        <span style="opacity:0.85;"> · Kelas {{ $selectedClass }} · Semester {{ $semester }} ({{ $tahunNama }}) · {{ count($students) }} Siswa · Kertas {{ $paperSize }} Landscape</span>
    </div>
    <div>
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF ({{ $paperSize }} Landscape)</button>
        <button onclick="window.close()" style="background: rgba(255,255,255,0.2);">Tutup</button>
    </div>
</div>

<div class="sheet">
    <!-- Kop Surat 2 Logo -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($logoLeft)
                    <img src="{{ $logoLeft }}" alt="Logo Yayasan">
                @endif
            </td>
            <td class="kop-center">
                <div class="kop-yayasan">{{ $headerYayasan }}</div>
                <div class="kop-sekolah">{{ $headerSekolah }}</div>
                <div class="kop-alamat">{{ $alamatSekolah }}</div>
                <div class="kop-judul">LEGER RAPORT HASIL BELAJAR PESERTA DIDIK</div>
            </td>
            <td class="kop-logo">
                @if($logoRight)
                    <img src="{{ $logoRight }}" alt="Logo Sekolah">
                @endif
            </td>
        </tr>
    </table>
    <div class="kop-divider"></div>

    <!-- Metadata Kelas -->
    <table class="meta-table">
        <tr>
            <td style="width: 15%;">Kelas / Rombel</td>
            <td style="width: 2%;">:</td>
            <td style="width: 33%;">{{ $selectedClass }}</td>
            <td style="width: 15%;">Tahun Pelajaran</td>
            <td style="width: 2%;">:</td>
            <td style="width: 33%;">{{ str_replace('/', ' - ', $tahunNama) }}</td>
        </tr>
        <tr>
            <td>Konsentrasi Keahlian</td>
            <td>:</td>
            <td>{{ $konsentrasiKeahlian }}</td>
            <td>Semester</td>
            <td>:</td>
            <td>{{ $semester }}</td>
        </tr>
        <tr>
            <td>Wali Kelas</td>
            <td>:</td>
            <td>{{ $waliNama }}</td>
            <td>Jumlah Siswa</td>
            <td>:</td>
            <td>{{ count($students) }} Peserta Didik</td>
        </tr>
    </table>

    <!-- Tabel Leger -->
    <table class="leger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 24px;">No</th>
                <th rowspan="2" style="width: 64px;">NISN</th>
                <th rowspan="2" style="width: 148px;">Nama Peserta Didik</th>
                @if($subjects->isNotEmpty())
                    <th colspan="{{ count($subjects) }}">Mata Pelajaran</th>
                @endif
                <th rowspan="2" style="width: 44px;">Jumlah</th>
                <th rowspan="2" style="width: 42px;">Rata-rata</th>
                <th rowspan="2" style="width: 30px;">Rank</th>
                <th colspan="3" style="width: 72px;">Ketidakhadiran</th>
            </tr>
            <tr>
                @foreach($subjects as $mapel)
                    <th class="leger-subhead">{{ $mapel->nama }}</th>
                @endforeach
                <th style="width: 24px;">S</th>
                <th style="width: 24px;">I</th>
                <th style="width: 24px;">A</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $idx => $siswa)
                @php
                    $scoresByMapel = $stsScores[$siswa->id] ?? collect();
                    $scoreMap = $scoresByMapel->mapWithKeys(fn ($rec) => [
                        $rec->mata_pelajaran_id => ($rec->final_score ?? $rec->asat_score ?? $rec->sts_score)
                    ]);
                    $summary = $stsSummaries[$siswa->id] ?? null;
                    $att = $attendanceMap[$siswa->id] ?? ['sakit' => 0, 'izin' => 0, 'alfa' => 0];
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $siswa->nisn ?: '-' }}</td>
                    <td class="text-left font-bold" style="padding-left: 4px;">{{ $siswa->nama_lengkap }}</td>
                    @foreach($subjects as $mapel)
                        @php $val = $scoreMap[$mapel->id] ?? null; @endphp
                        <td class="text-center">{{ $val !== null ? number_format($val, 1) : '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold bg-summary">{{ $summary?->total_score !== null ? number_format($summary->total_score, 1) : '-' }}</td>
                    <td class="text-center font-bold bg-summary">{{ $summary?->average_score !== null ? number_format($summary->average_score, 1) : '-' }}</td>
                    <td class="text-center font-bold bg-summary">{{ $summary?->rank ?? '-' }}</td>
                    <td class="text-center">{{ $att['sakit'] }}</td>
                    <td class="text-center">{{ $att['izin'] }}</td>
                    <td class="text-center">{{ $att['alfa'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($subjects) + 9 }}" class="text-center" style="padding: 14px;">
                        Tidak ada data siswa pada kelas {{ $selectedClass }}.
                    </td>
                </tr>
            @endforelse

            @if($students->isNotEmpty())
                <tr class="bg-stat">
                    <td colspan="3" class="text-left" style="padding-left: 6px;">Rata-rata Kelas</td>
                    @foreach($subjects as $mapel)
                        @php $avgVal = $subjectStats[$mapel->id]['avg'] ?? null; @endphp
                        <td class="text-center">{{ $avgVal !== null ? number_format($avgVal, 1) : '-' }}</td>
                    @endforeach
                    <td class="text-center">{{ $overallStats['total_avg'] !== null ? number_format($overallStats['total_avg'], 1) : '-' }}</td>
                    <td class="text-center">{{ $overallStats['mean_avg'] !== null ? number_format($overallStats['mean_avg'], 1) : '-' }}</td>
                    <td colspan="4"></td>
                </tr>
                <tr class="bg-stat">
                    <td colspan="3" class="text-left" style="padding-left: 6px;">Nilai Tertinggi</td>
                    @foreach($subjects as $mapel)
                        @php $maxVal = $subjectStats[$mapel->id]['max'] ?? null; @endphp
                        <td class="text-center">{{ $maxVal !== null ? number_format($maxVal, 1) : '-' }}</td>
                    @endforeach
                    <td class="text-center">{{ $overallStats['total_max'] !== null ? number_format($overallStats['total_max'], 1) : '-' }}</td>
                    <td class="text-center">{{ $overallStats['mean_max'] !== null ? number_format($overallStats['mean_max'], 1) : '-' }}</td>
                    <td colspan="4"></td>
                </tr>
                <tr class="bg-stat">
                    <td colspan="3" class="text-left" style="padding-left: 6px;">Nilai Terendah</td>
                    @foreach($subjects as $mapel)
                        @php $minVal = $subjectStats[$mapel->id]['min'] ?? null; @endphp
                        <td class="text-center">{{ $minVal !== null ? number_format($minVal, 1) : '-' }}</td>
                    @endforeach
                    <td class="text-center">{{ $overallStats['total_min'] !== null ? number_format($overallStats['total_min'], 1) : '-' }}</td>
                    <td class="text-center">{{ $overallStats['mean_min'] !== null ? number_format($overallStats['mean_min'], 1) : '-' }}</td>
                    <td colspan="4"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <div class="footer-keep-together">
        <table class="sig-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    Kepala Sekolah
                    <div class="sig-space">
                        @if(!empty($raporSettings?->ttd_kepala_sekolah))
                            <img src="{{ asset('storage/' . $raporSettings->ttd_kepala_sekolah) }}" alt="TTD Kepala Sekolah" style="max-height: 48px; max-width: 130px; object-fit: contain;">
                        @endif
                        @if(!empty($raporSettings?->stempel_sekolah))
                            <img src="{{ asset('storage/' . $raporSettings->stempel_sekolah) }}" alt="Stempel" style="max-height: 52px; margin-left: -40px; opacity: 0.85; pointer-events: none;">
                        @endif
                    </div>
                    <strong><u>{{ $kepsekNama }}</u></strong><br>
                    <span>NIP. {{ $kepsekNip }}</span>
                </td>
                <td>
                    {{ $kotaPembagian }}, {{ $tanggalCetak }}<br>
                    Wali Kelas {{ $selectedClass }}
                    <div class="sig-space">
                        @if(!empty($homeroom?->ttd_wali_kelas))
                            <img src="{{ asset('storage/' . $homeroom->ttd_wali_kelas) }}" alt="TTD Wali Kelas" style="max-height: 48px; max-width: 130px; object-fit: contain;">
                        @endif
                    </div>
                    <strong><u>{{ $waliNama }}</u></strong><br>
                    <span>NIP. {{ $waliNip }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
