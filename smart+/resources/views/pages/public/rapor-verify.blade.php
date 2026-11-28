<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian Arsip Rapor Digital — SMKS PGRI 5 Serpong</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 24px 16px;
        }
        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }
        .banner {
            padding: 24px 28px;
            color: #ffffff;
        }
        .banner.valid { background: #065f46; }
        .banner.invalid { background: #991b1b; }
        .banner.not-found { background: #334155; }
        .banner h1 { margin: 0 0 6px 0; font-size: 20px; font-weight: 700; }
        .banner p { margin: 0; font-size: 13px; opacity: 0.92; }
        .body { padding: 24px 28px; }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }
        .meta-grid th, .meta-grid td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
            vertical-align: top;
        }
        .meta-grid th {
            width: 38%;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .meta-grid td { font-weight: 600; color: #0f172a; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-valid { background: #d1fae5; color: #065f46; }
        .badge-invalid { background: #fee2e2; color: #991b1b; }
        .footer {
            padding: 14px 28px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        @if(!$found)
            <div class="banner not-found">
                <h1>Arsip Rapor Tidak Ditemukan</h1>
                <p>Token verifikasi QR Code tidak terdaftar pada database arsip resmi sekolah.</p>
            </div>
            <div class="body">
                <p style="margin:0; font-size:14px; line-height:1.6; color:#475569;">
                    Pastikan Anda memindai QR Code langsung dari dokumen Rapor Digital resmi sekolah. Hubungi Tata Usaha sekolah apabila diperlukan konfirmasi keabsahan dokumen fisik atau digital alumni.
                </p>
            </div>
        @else
            @php
                $school = $payload['school'] ?? [];
                $student = $payload['student'] ?? [];
                $period = $payload['period'] ?? [];
                $summary = $payload['summary'] ?? [];
                $signatories = $payload['signatories'] ?? [];
                $gradesCount = count($payload['grades'] ?? []);
            @endphp
            <div class="banner {{ $integrityValid ? 'valid' : 'invalid' }}">
                <h1>
                    {{ $integrityValid ? 'Dokumen Rapor Digital Terverifikasi Asli' : 'Peringatan: Integritas Dokumen Tidak Cocok' }}
                </h1>
                <p>
                    {{ $school['nama_sekolah'] ?? 'SMKS PGRI 5 Serpong' }} — Arsip Resmi Sistem Informasi Akademik SMART+
                </p>
            </div>
            <div class="body">
                <div style="margin-bottom: 12px;">
                    @if($integrityValid)
                        <span class="badge badge-valid">SHA-256 INTEGRITY VALID · TERKUNCI PERMANEN (v{{ $archive->version }})</span>
                    @else
                        <span class="badge badge-invalid">INTEGRITY MISMATCH · PERLU PENGECEKAN ADMIN</span>
                    @endif
                </div>

                <table class="meta-grid">
                    <tr>
                        <th>Nomor Arsip</th>
                        <td class="mono">{{ $archive->archive_number }}</td>
                    </tr>
                    <tr>
                        <th>Nama Peserta Didik</th>
                        <td>{{ $student['nama_lengkap'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>NIPD / NISN</th>
                        <td class="mono">{{ $student['nipd'] ?? '-' }} / {{ $student['nisn'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Kelas Saat Periode Ini</th>
                        <td>{{ $student['kelas'] ?? $archive->kelas }}</td>
                    </tr>
                    <tr>
                        <th>Periode &amp; Jenis Rapor</th>
                        <td>Tahun Ajaran {{ $period['tahun_ajaran_nama'] ?? '-' }} · Semester {{ $archive->semester }} · {{ strtoupper($archive->jenis_rapor) }}</td>
                    </tr>
                    <tr>
                        <th>Rata-rata Nilai / Mapel</th>
                        <td>
                            {{ isset($summary['rata_rata']) && $summary['rata_rata'] !== null ? number_format((float) $summary['rata_rata'], 1) : '-' }}
                            ({{ $gradesCount }} mata pelajaran tercatat)
                        </td>
                    </tr>
                    <tr>
                        <th>Peringkat Kelas</th>
                        <td>
                            @if(!empty($summary['rank']))
                                Peringkat ke-{{ $summary['rank'] }} dari {{ $summary['total_siswa_kelas'] ?? '-' }} siswa
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Wali Kelas &amp; Kepala Sekolah</th>
                        <td>
                            {{ $signatories['wali_kelas_nama'] ?? '-' }}<br>
                            <span style="color:#475569; font-weight:500;">Kepsek: {{ $signatories['kepala_sekolah_nama'] ?? '-' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal Pengesahan Arsip</th>
                        <td>{{ $archive->finalized_at?->translatedFormat('d F Y H:i') ?? '-' }} WIB</td>
                    </tr>
                    <tr>
                        <th>Sidik Jari Digital (SHA-256)</th>
                        <td class="mono" style="word-break: break-all;">{{ $archive->document_hash }}</td>
                    </tr>
                </table>
            </div>
        @endif
        <div class="footer">
            Sistem Arsip Rapor Digital SMART+ · Data di halaman ini dibaca langsung dari snapshot arsip permanen yang disahkan sekolah.
        </div>
    </div>
</body>
</html>
