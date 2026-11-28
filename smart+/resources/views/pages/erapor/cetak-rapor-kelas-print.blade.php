<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $paperSize = $raporSettings?->ukuran_kertas ?? 'A4';
        $cssPageSize = match($paperSize) {
            'F4' => '215mm 330mm',
            'Letter' => 'letter',
            default => 'A4',
        };
        $showRanking = $raporSettings?->show_ranking ?? true;
        $isSts = ($jenisRapor === 'sts');

        // Resolve logo safely (base64 if file exists on disk to prevent broken image icon)
        $logoSrc = null;
        if (!empty($schoolSettings?->logo)) {
            $rawLogo = ltrim($schoolSettings->logo, '/');
            $candidates = [
                storage_path('app/public/' . $rawLogo),
                public_path('storage/' . $rawLogo),
                public_path($rawLogo),
            ];
            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $mime = mime_content_type($candidate) ?: 'image/png';
                    $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
                    break;
                }
            }
        }
    @endphp
    <title>Cetak Semua Rapor Kelas {{ $kelas }} - {{ strtoupper($jenisRapor) }} ({{ $semester }} {{ $targetYear->nama }})</title>
    <style>
        @page {
            size: {{ $cssPageSize }} portrait;
            margin: 8mm 14mm 8mm 14mm;
        }

        body {
            font-family: {{ $isSts ? "'Times New Roman', Times, serif" : "Arial, Helvetica, sans-serif" }};
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 9.5pt;
            line-height: 1.25;
        }

        .rapor-page {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            padding-top: 4px;
        }

        .rapor-page.sts-mode {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* ==================== UAS 2-PAGE OFFICIAL FORMAT ==================== */
        .uas-sheet {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.8pt;
            line-height: 1.25;
            color: #000;
        }
        .uas-page-2 {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 2px dashed #cbd5e1;
        }
        .uas-kop-table { width: 100%; border-collapse: collapse; }
        .uas-kop-table td { vertical-align: middle; }
        .uas-kop-logo { width: 82px; text-align: center; }
        .uas-kop-logo img { max-width: 68px; max-height: 68px; object-fit: contain; }
        .uas-kop-center { text-align: center; line-height: 1.25; }
        .uas-kop-yayasan { font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .uas-kop-sekolah { font-size: 13.5pt; font-weight: bold; color: #0033cc; text-transform: uppercase; letter-spacing: 0.3px; margin: 1px 0; }
        .uas-kop-alamat { font-size: 8.3pt; color: #111; }
        .uas-kop-judul { font-size: 10.5pt; font-weight: bold; text-transform: uppercase; margin-top: 3px; }
        .uas-kop-tahun { font-size: 9.2pt; margin-top: 1px; }
        .uas-line-top { border-top: 1.5px solid #000; margin: 5px 0 6px 0; }
        .uas-line-bottom { border-bottom: 1.5px solid #000; margin: 5px 0 9px 0; }
        .uas-biodata { width: 100%; border-collapse: collapse; font-size: 8.6pt; font-weight: bold; }
        .uas-biodata td { padding: 1.5px 0; vertical-align: top; }
        .uas-bio-l1 { width: 19%; }
        .uas-bio-col { width: 2%; text-align: center; }
        .uas-bio-v1 { width: 41%; }
        .uas-bio-l2 { width: 11%; }
        .uas-bio-v2 { width: 25%; }
        .uas-section-heading { font-weight: bold; font-size: 9pt; margin-bottom: 5px; }
        .uas-grade-table { width: 100%; border-collapse: collapse; font-size: 8.1pt; }
        .uas-grade-table th, .uas-grade-table td { border: 1px solid #000; padding: 3px 5px; line-height: 1.18; }
        .uas-grade-table th { background-color: #d9d9d9; font-weight: bold; text-align: center; padding: 5px 4px; vertical-align: middle; }
        .uas-group-row { font-weight: bold; background-color: #fff; padding: 3px 5px !important; font-size: 8.1pt; }
        .uas-capaian-cell { font-size: 7.5pt; line-height: 1.16 !important; text-align: left; }
        .uas-summary-row td { padding: 3.5px 6px; }
        .uas-simple-table { width: 100%; border-collapse: collapse; font-size: 8.6pt; }
        .uas-simple-table th, .uas-simple-table td { border: 1px solid #000; padding: 3.5px 6px; }
        .uas-simple-table th { font-weight: bold; background-color: #fff; }
        .uas-catatan-box { border: 1px solid #000; padding: 8px 10px; min-height: 36px; font-size: 8.6pt; font-style: italic; line-height: 1.35; margin-bottom: 20px; }
        .uas-bottom-grid { width: 100%; border-collapse: collapse; font-size: 8.6pt; page-break-inside: avoid; break-inside: avoid; }
        .uas-bottom-grid > tbody > tr > td { vertical-align: top; }
        .uas-bottom-left { width: 48%; padding-top: 6px; padding-right: 16px; }
        .uas-bottom-right { width: 52%; }
        .uas-keputusan-box { border: 1px solid #000; padding: 8px 12px 12px 12px; line-height: 1.32; }
        .uas-keputusan-rows { border-collapse: collapse; margin-top: 2px; }
        .uas-keputusan-rows td { padding: 1px 6px 1px 0; border: none; }
        .uas-kepsek-sign { margin-top: 22px; padding-left: 36%; }
        .uas-ttd-space { height: 52px; display: flex; align-items: center; }
        @media print {
            .uas-page-2 {
                margin-top: 0 !important;
                padding-top: 0 !important;
                border-top: none !important;
                page-break-before: always;
                break-before: page;
            }
        }

        /* Page Break for Bulk Print */
        .page-break {
            page-break-after: always;
            break-after: page;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .kop-table td {
            vertical-align: middle;
        }

        .kop-logo {
            width: 68px;
            text-align: center;
        }

        .kop-logo img {
            max-width: 58px;
            max-height: 58px;
            object-fit: contain;
        }

        .kop-logo-badge {
            width: 52px;
            height: 52px;
            border: 1.5px solid #000;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14pt;
            letter-spacing: 0.5px;
        }

        .kop-text {
            text-align: center;
            padding-right: 30px;
        }

        .kop-text h4 {
            margin: 0;
            font-size: 9.5pt;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text p {
            margin: 0;
            font-size: 8.5pt;
            color: #222;
            line-height: 1.2;
        }

        .kop-divider {
            border-top: 2px solid #000;
            border-bottom: 0.5px solid #000;
            height: 1px;
            margin: 4px 0 9px 0;
        }

        /* Document Title */
        .doc-title {
            text-align: center;
            margin-bottom: 9px;
        }

        .doc-title h3 {
            margin: 0;
            font-size: 10.5pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 9pt;
            color: #222;
            font-weight: bold;
        }

        /* Biodata */
        .biodata-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 9px;
        }

        .biodata-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .biodata-label {
            width: 17%;
        }

        .biodata-colon {
            width: 2%;
            text-align: center;
        }

        .biodata-val {
            width: 35%;
            font-weight: bold;
        }

        .biodata-label-right {
            width: 15%;
        }

        .biodata-val-right {
            width: 29%;
            font-weight: bold;
        }

        /* Grade Table */
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 9px;
        }

        .grade-table th, .grade-table td {
            border: 1px solid #000;
            padding: {{ $isSts ? '4px 6px' : '3px 5px' }};
            line-height: 1.2;
        }

        .grade-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            padding: 5px 6px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        /* Attendance & Notes */
        .footer-keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 8.5pt;
        }

        .info-box-cell {
            border: 1px solid #000;
            padding: 7px 10px;
            vertical-align: top;
        }

        .info-spacer-cell {
            width: 12px;
            border: none;
        }

        .sub-box-title {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 5px;
            border-bottom: 0.5px solid #999;
            padding-bottom: 3px;
        }

        /* Signature block */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9pt;
            line-height: 1.35;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-table td {
            vertical-align: top;
            text-align: center;
            width: 33.33%;
        }

        .sig-space {
            height: 50px;
        }

        /* Toolbar (screen only) */
        .no-print {
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: sans-serif;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .no-print button {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .no-print button:hover {
            background: #059669;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .rapor-page {
                padding-top: 0 !important;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Action Toolbar (hidden on print) -->
@php
    $schoolNameGlobal = $schoolSettings?->nama_sekolah ?? 'SMKS PGRI 5 Serpong';
@endphp
<div class="no-print">
    <div>
        <strong>{{ $schoolNameGlobal }}</strong> — Cetak Masal Rapor {{ strtoupper($jenisRapor) }} Kelas {{ $kelas }} ({{ count($raporData) }} Siswa • Kertas {{ $paperSize }})
    </div>
    <div style="display: flex; align-items: center;">
        <span style="background: {{ $jenisRapor === 'sts' ? '#d97706' : '#2563eb' }}; color: #fff; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: bold;">
            {{ $jenisRapor === 'sts' ? 'Format Rapor UTS (Semester Ganjil)' : 'Format Rapor UAS (Semester Genap)' }}
        </span>
        <button onclick="window.print()">🖨️ Cetak Semua ({{ count($raporData) }} Lembar {{ $paperSize }})</button>
        <button onclick="window.close()" style="background: rgba(255,255,255,0.2);">Tutup</button>
    </div>
</div>

@foreach($raporData as $dataIdx => $item)
    @php
        $siswa = $item['siswa'];
        $studentData = $item['studentData'] ?? [];
        $schoolData = $item['schoolData'] ?? [];
        $formatData = $item['formatData'] ?? [];
        $itemRaporSettings = $item['raporSettings'] ?? $raporSettings;
        $itemSchoolSettings = $item['schoolSettings'] ?? $schoolSettings;
        $itemHomeroom = $item['homeroom'] ?? $homeroom;
        $itemWali = $item['waliKelas'] ?? $waliKelas;
        $itemKepsek = $item['kepalaSekolah'] ?? $kepalaSekolah;
        $itemTanggal = $item['tanggalRaporFormatted'] ?? $tanggalRaporFormatted;
        $itemSchoolName = $schoolData['nama_sekolah'] ?? $itemSchoolSettings?->nama_sekolah ?? $schoolNameGlobal;
        $displayClass = $item['kelasHistoris'] ?? $studentData['kelas'] ?? $kelas;
        $displayNisnNipd = trim(($studentData['nipd'] ?? $siswa->nipd ?? '') . ' / ' . ($studentData['nisn'] ?? $siswa->nisn ?? ''), ' /');
        $gradeItems = $item['gradeItems'];
        $totalNilai = $item['totalNilai'];
        $rataRata = $item['rataRata'];
        $rataRataKelasItem = $item['rataRataKelas'] ?? $rataRataKelas ?? null;
        $stsSummary = $item['stsSummary'];
        $attendance = $item['attendance'];
        $itemArchive = $item['archive'] ?? null;
        $itemQrSvg = $item['qrSvg'] ?? null;
        $isLast = $dataIdx === (count($raporData) - 1);
    @endphp

    <div class="rapor-page {{ $isSts ? 'sts-mode' : 'uas-mode' }} {{ !$isLast ? 'page-break' : '' }}">
        @if($jenisRapor === 'sts')
            <!-- Kop Surat Resmi (STS) -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" alt="Logo">
                        @else
                            <div class="kop-logo-badge">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $itemSchoolName), 0, 2) ?: 'SM') }}</div>
                        @endif
                    </td>
                    <td class="kop-text">
                        <h4>{{ $itemRaporSettings?->header_yayasan ?? 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP) PGRI' }}</h4>
                        <h2>{{ $itemRaporSettings?->header_sekolah ?? strtoupper($itemSchoolName) }}</h2>
                        <p>{{ $schoolData['alamat_sekolah'] ?? $itemSchoolSettings?->alamat_sekolah ?? '-' }}</p>
                        <p>Telp: {{ $schoolData['no_telepon_sekolah'] ?? $itemSchoolSettings?->no_telepon_sekolah ?? '-' }} | Email: {{ $schoolData['email_sekolah'] ?? $itemSchoolSettings?->email_sekolah ?? '-' }}</p>
                    </td>
                </tr>
            </table>
            <div class="kop-divider"></div>

            <!-- Judul Dokumen (STS) -->
            <div class="doc-title">
                <h3>
                    {{ $formatData['judul_rapor'] ?? $itemRaporSettings?->getEffectiveJudulRapor($jenisRapor) ?? 'LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)' }}
                </h3>
                <p>
                    @if($itemRaporSettings?->show_kurikulum && !empty($itemRaporSettings?->nama_kurikulum))
                        {{ strtoupper($itemRaporSettings->nama_kurikulum) }} — 
                    @endif
                    TAHUN AJARAN {{ strtoupper($targetYear->nama) }}
                </p>
            </div>

            <!-- Identitas Peserta Didik (STS) -->
            <table class="biodata-table">
                <tr>
                    <td class="biodata-label">Nama Peserta Didik</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val">{{ strtoupper($studentData['nama_lengkap'] ?? $siswa->nama_lengkap) }}</td>

                    <td class="biodata-label-right">Kelas{{ $itemRaporSettings?->show_fase ? ' / Fase' : '' }}</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val-right">{{ $displayClass }}{{ $itemRaporSettings?->show_fase ? ' / ' . ($itemRaporSettings->fase_text ?: 'Fase E') : '' }}</td>
                </tr>
                <tr>
                    <td class="biodata-label">NISN / NIS</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val">{{ $displayNisnNipd ?: '-' }}</td>

                    <td class="biodata-label-right">Semester</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val-right">{{ $semester }} ({{ $semester === 'Ganjil' ? 'Satu' : 'Dua' }})</td>
                </tr>
                <tr>
                    <td class="biodata-label">Nama Sekolah</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val">{{ $itemSchoolName }}</td>

                    <td class="biodata-label-right">Tahun Pelajaran</td>
                    <td class="biodata-colon">:</td>
                    <td class="biodata-val-right">{{ $targetYear->nama }}</td>
                </tr>
            </table>

            <!-- Tabel Capaian Nilai Akademik (STS) -->
            @php
                $fmtScore = function ($val) {
                    if ($val === null || $val === '') return '-';
                    $num = (float) $val;
                    return floor($num) == $num ? number_format($num, 0) : number_format($num, 1);
                };
            @endphp
            <table class="grade-table" style="font-size: 8.5pt;">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 24px; vertical-align: middle;">No</th>
                        <th rowspan="2" style="text-align: center; vertical-align: middle;">Mata Pelajaran</th>
                        @if(!empty($showKkm))
                            <th rowspan="2" style="width: 36px; text-align: center; vertical-align: middle;">KKM</th>
                        @endif
                        <th colspan="3" style="text-align: center;">Ulangan Harian</th>
                        <th colspan="3" style="text-align: center;">Tugas</th>
                        <th colspan="2" style="text-align: center;">Praktek</th>
                        <th colspan="2" style="text-align: center;">Projek</th>
                        <th colspan="2" style="text-align: center;">Portofolio</th>
                        <th rowspan="2" style="width: 52px; text-align: center; vertical-align: middle;">Nilai PTS</th>
                    </tr>
                    <tr>
                        @for($i = 1; $i <= 3; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 3; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @php
                        $stsColspan = !empty($showKkm) ? 16 : 15;
                        $groupTitles = [
                            'umum' => 'A. KELOMPOK MATA PELAJARAN UMUM',
                            'kejuruan' => 'B. KELOMPOK MATA PELAJARAN KEJURUAN',
                            'konsentrasi' => 'C. KONSENTRASI KEAHLIAN',
                            'pilihan' => 'D. MATA PELAJARAN PILIHAN',
                            'mulok' => 'E. MUATAN LOKAL',
                        ];
                        $distinctGroups = collect($gradeItems)->pluck('kelompok')->filter()->unique()->count();
                        $lastGroup = null;
                    @endphp
                    @foreach($gradeItems as $idx => $g)
                        @php
                            $grp = $g['kelompok'] ?? 'umum';
                            $skorSts = $g['nilai_sts'] ?? $g['sumatif'] ?? $g['nilai_rapor'];
                        @endphp
                        @if($distinctGroups > 1 && $grp !== $lastGroup)
                            <tr>
                                <td colspan="{{ $stsColspan }}" style="padding: 3.5px 6px; font-weight: bold; background-color: #eef2f7; font-size: 8pt;">
                                    {{ $g['kelompok_heading'] ?? ($groupTitles[$grp] ?? strtoupper($g['kelompok_label'] ?? $grp)) }}
                                </td>
                            </tr>
                            @php $lastGroup = $grp; @endphp
                        @endif
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">{{ $idx + 1 }}</td>
                            <td style="padding: 3px 6px; font-weight: 600;">{{ $g['nama'] }}</td>
                            @if(!empty($showKkm))
                                <td class="text-center font-bold" style="padding: 3px 2px;">
                                    {{ isset($g['kkm']) ? (floor($g['kkm']) == $g['kkm'] ? number_format($g['kkm'], 0) : number_format($g['kkm'], 1)) : '-' }}
                                </td>
                            @endif
                            @for($i = 1; $i <= 3; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['uh'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 3; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['tugas'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['praktek'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['projek'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['portofolio'][$i] ?? null) }}</td>
                            @endfor
                            <td class="text-center font-bold" style="padding: 3px 2px;">
                                {{ $fmtScore($skorSts) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Tabel Ketidakhadiran & Jumlah + Tanda Tangan (Format STS/PTS) -->
            <div class="footer-keep-together">
                <table class="grade-table" style="margin-top: 10px; font-size: 8.5pt;">
                    <thead>
                        <tr>
                            <th colspan="3" style="text-align: center; width: 48%; padding: 4px;">Ketidakhadiran</th>
                            <th colspan="3" style="text-align: center; width: 52%; padding: 4px;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center" style="width: 24px; padding: 3px 2px;">1</td>
                            <td style="padding: 3px 6px;">Sakit</td>
                            <td class="text-center" style="width: 85px; padding: 3px 4px;">{{ $attendance->s ?? 0 }} Hari</td>
                            <td class="text-center" style="width: 24px; padding: 3px 2px;">4</td>
                            <td style="padding: 3px 6px;">Nilai Total PTS</td>
                            <td class="text-center font-bold" style="width: 85px; padding: 3px 4px;">
                                {{ $totalNilai > 0 ? $fmtScore($totalNilai) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">2</td>
                            <td style="padding: 3px 6px;">Izin</td>
                            <td class="text-center" style="padding: 3px 4px;">{{ $attendance->i ?? 0 }} Hari</td>
                            <td class="text-center" style="padding: 3px 2px;">5</td>
                            <td style="padding: 3px 6px;">Nilai Rata-rata Siswa</td>
                            <td class="text-center font-bold" style="padding: 3px 4px;">
                                {{ $rataRata !== null ? number_format($rataRata, 2) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">3</td>
                            <td style="padding: 3px 6px;">Tanpa Keterangan</td>
                            <td class="text-center" style="padding: 3px 4px;">{{ $attendance->a ?? 0 }} Hari</td>
                            <td class="text-center" style="padding: 3px 2px;">6</td>
                            <td style="padding: 3px 6px;">Nilai Rata-rata Kelas</td>
                            <td class="text-center font-bold" style="padding: 3px 4px;">
                                {{ ($rataRataKelas ?? null) !== null ? number_format($rataRataKelas, 2) : '-' }}
                            </td>
                        </tr>
                        @if($showRanking)
                            <tr>
                                <td colspan="3" style="padding: 3px 6px; font-style: italic; font-size: 8pt; color: #333;">
                                    @if(!empty($stsSummary?->catatan_wali_kelas))
                                        Catatan: "{{ $stsSummary->catatan_wali_kelas }}"
                                    @endif
                                </td>
                                <td class="text-center" style="padding: 3px 2px;">7</td>
                                <td style="padding: 3px 6px; font-weight: bold;">Peringkat Kelas</td>
                                <td class="text-center font-bold" style="padding: 3px 4px;">
                                    @if($stsSummary?->rank)
                                        Ke-{{ $stsSummary->rank }} / {{ $totalSiswaKelas }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Tanda Tangan STS/PTS (2 Kolom: Wali Murid & Wali Kelas) -->
                <table class="sig-table" style="margin-top: 20px;">
                    <tr>
                        <td style="width: 50%; text-align: center;">
                            <br>
                            Wali Murid
                            <div class="sig-space"></div>
                            <strong>( ..................................................... )</strong>
                        </td>
                        <td style="width: 50%; text-align: center;">
                            {{ $itemRaporSettings?->kota_pembagian ?? 'Kota Tangerang Selatan' }}, {{ $itemTanggal }}<br>
                            Wali Kelas {{ $displayClass }}
                            <div class="sig-space" style="display: flex; align-items: center; justify-content: center;">
                                @if(!empty($itemHomeroom?->ttd_wali_kelas))
                                    <img src="{{ asset('storage/' . $itemHomeroom->ttd_wali_kelas) }}" alt="TTD Wali Kelas" style="max-height: 45px; max-width: 125px; object-fit: contain;">
                                @endif
                            </div>
                            <strong>( {{ $itemHomeroom?->effective_nama ?? $itemWali?->nama_lengkap ?? 'Wali Kelas' }} )</strong>
                        </td>
                    </tr>
                </table>
            </div>
        @else
            @include('pages.erapor.partials.rapor-uas-pages', [
                'siswa' => $siswa,
                'studentData' => $studentData,
                'schoolData' => $schoolData,
                'formatData' => $formatData,
                'itemRaporSettings' => $itemRaporSettings,
                'itemSchoolSettings' => $itemSchoolSettings,
                'itemHomeroom' => $itemHomeroom,
                'itemWali' => $itemWali,
                'itemKepsek' => $itemKepsek,
                'itemTanggal' => $itemTanggal,
                'itemSchoolName' => $itemSchoolName,
                'displayClass' => $displayClass,
                'displayNisnNipd' => $displayNisnNipd,
                'targetYear' => $targetYear,
                'semester' => $semester,
                'gradeItems' => $gradeItems,
                'totalNilai' => $totalNilai,
                'rataRata' => $rataRata,
                'showRanking' => $showRanking,
                'stsSummary' => $stsSummary,
                'totalSiswaKelas' => $totalSiswaKelas,
                'attendance' => $attendance,
                'ekstrakurikuler' => $item['ekstrakurikuler'] ?? null,
                'keputusan' => $item['keputusan'] ?? null,
                'resolvedFase' => $item['resolvedFase'] ?? null,
                'konsentrasiKeahlian' => $item['konsentrasiKeahlian'] ?? null,
                'itemArchive' => $itemArchive,
                'itemQrSvg' => $itemQrSvg,
            ])
        @endif

        @if($jenisRapor === 'sts' && $itemArchive && $itemQrSvg)
            <div class="footer-keep-together" style="margin-top: 14px; border-top: 1px dashed #64748b; padding-top: 8px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div style="font-size: 7.5pt; color: #334155; line-height: 1.35;">
                    <strong>ARSIP RAPOR DIGITAL RESMI — {{ strtoupper($itemSchoolName) }}</strong><br>
                    No. Arsip: <span style="font-family: monospace;">{{ $itemArchive->archive_number }}</span> · Versi: v{{ $itemArchive->version }} · Disahkan: {{ $itemArchive->finalized_at?->translatedFormat('d M Y H:i') }} WIB<br>
                    Sidik Jari Digital (SHA-256): <span style="font-family: monospace;">{{ substr($itemArchive->document_hash, 0, 24) }}...</span>
                </div>
                <div style="flex-shrink: 0; border: 1px solid #cbd5e1; padding: 3px; background: #fff;">
                    {!! $itemQrSvg !!}
                </div>
            </div>
        @endif
    </div>
@endforeach

</body>
</html>
