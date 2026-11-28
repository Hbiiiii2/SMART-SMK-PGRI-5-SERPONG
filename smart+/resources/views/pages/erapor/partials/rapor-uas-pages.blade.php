@php
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

    $uasLogoLeft = $resolveImgDataUri($itemRaporSettings?->logo_yayasan ?? ($schoolData['logo_yayasan'] ?? null), 'images/rapor/logo-pgri.png');
    $uasLogoRight = $resolveImgDataUri($itemSchoolSettings?->logo ?? ($schoolData['logo'] ?? null), 'images/rapor/logo-smk-pgri5.png');

    $uasHeaderYayasan = $itemRaporSettings?->header_yayasan ?: 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP)';
    $uasHeaderSekolah = $itemRaporSettings?->header_sekolah ?: strtoupper($itemSchoolName);
    $uasAlamatSekolah = $schoolData['alamat_sekolah'] ?? $itemSchoolSettings?->alamat_sekolah ?? 'Jl. Raya Puspiptek (Depan Masjid Agung) Serpong - Tangerang Selatan';
    $uasJudulRapor = $formatData['judul_rapor'] ?? $itemRaporSettings?->getEffectiveJudulRapor('uas') ?? 'LAPORAN HASIL BELAJAR';
    $uasTahunPelajaran = str_replace('/', ' - ', (string) ($targetYear->nama ?? '-'));

    $uasNamaSiswa = strtoupper($studentData['nama_lengkap'] ?? $siswa->nama_lengkap);
    $uasNisNisn = $displayNisnNipd ?: '-';
    $uasKonsentrasi = $konsentrasiKeahlian
        ?? $studentData['konsentrasi_keahlian']
        ?? $studentData['kompetensi_keahlian']
        ?? (\App\Models\Rombel::inferMetaFromName((string) $displayClass)['kompetensi_keahlian'] ?? '-');
    $uasFase = $resolvedFase
        ?? $studentData['fase']
        ?? \App\Services\RaporArchiveService::resolveFase((string) $displayClass, $studentData['tingkat'] ?? null, $itemRaporSettings?->fase_text ?? null);

    $uasGroupTitles = [
        'umum' => 'A. KELOMPOK MATA PELAJARAN UMUM',
        'kejuruan' => 'B. KELOMPOK MATA PELAJARAN KEJURUAN',
        'konsentrasi' => 'C. KONSENTRASI KEAHLIAN',
        'pilihan' => 'D. MATA PELAJARAN PILIHAN',
        'mulok' => 'E. MUATAN LOKAL',
    ];
    $distinctGroupsUas = collect($gradeItems)->pluck('kelompok')->filter()->unique()->count();
    $lastGroupUas = null;

    $ekskulList = \App\Services\RaporArchiveService::normalizeEkstrakurikuler($ekstrakurikuler ?? ($stsSummary?->ekstrakurikuler ?? []));
    while (count($ekskulList) < 3) {
        $ekskulList[] = ['nama' => '-', 'keterangan' => '-'];
    }

    $keputusanData = $keputusan
        ?? ($stsSummary?->keputusan ?? \App\Services\RaporArchiveService::resolveKeputusan(
            $studentData['tingkat'] ?? null,
            $semester,
            $stsSummary?->keputusan_status ?? null,
            $stsSummary?->keputusan_kelas ?? null
        ));
    $kepStatus = $keputusanData['status'] ?? 'pending';
    $kepNaikKe = $keputusanData['naik_ke'] ?? '-';
    $kepTinggalDi = $keputusanData['tinggal_di'] ?? '-';
    $kepLulusLabel = $keputusanData['label_lulus'] ?? null;

    $waliNama = $itemHomeroom?->effective_nama ?? $itemWali?->nama_lengkap ?? 'Wali Kelas';
    $waliNip = $itemHomeroom?->effective_nip ?? $itemWali?->nip ?? '-';
    $kepsekNama = $itemRaporSettings?->nama_kepala_sekolah ?? $itemKepsek?->name ?? 'Kepala Sekolah';
    $kepsekNip = $itemRaporSettings?->nip_kepala_sekolah ?: ($itemKepsek?->guru?->nip ?? '-');
    $kotaPembagian = $itemRaporSettings?->kota_pembagian ?: 'Kota Tangerang Selatan';
@endphp

<div class="uas-sheet">
    <!-- ==================== HALAMAN 1: NILAI AKADEMIK ==================== -->
    <div class="uas-page uas-page-1">
        <table class="uas-kop-table">
            <tr>
                <td class="uas-kop-logo">
                    @if($uasLogoLeft)
                        <img src="{{ $uasLogoLeft }}" alt="Logo Yayasan">
                    @else
                        <div class="kop-logo-badge">YPLP</div>
                    @endif
                </td>
                <td class="uas-kop-center">
                    <div class="uas-kop-yayasan">{{ $uasHeaderYayasan }}</div>
                    <div class="uas-kop-sekolah">{{ $uasHeaderSekolah }}</div>
                    <div class="uas-kop-alamat">{{ $uasAlamatSekolah }}</div>
                    <div class="uas-kop-judul">{{ $uasJudulRapor }}</div>
                    <div class="uas-kop-tahun">Tahun Pelajaran {{ $uasTahunPelajaran }}</div>
                </td>
                <td class="uas-kop-logo">
                    @if($uasLogoRight)
                        <img src="{{ $uasLogoRight }}" alt="Logo Sekolah">
                    @else
                        <div class="kop-logo-badge">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $itemSchoolName), 0, 3) ?: 'SMK') }}</div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="uas-line-top"></div>

        <!-- Identitas Siswa Halaman 1 -->
        <table class="uas-biodata">
            <tr>
                <td class="uas-bio-l1">Nama Siswa</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasNamaSiswa }}</td>
                <td class="uas-bio-l2">Kelas</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $displayClass }}</td>
            </tr>
            <tr>
                <td class="uas-bio-l1">NIS / NISN</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasNisNisn }}</td>
                <td class="uas-bio-l2">Fase</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $uasFase }}</td>
            </tr>
            <tr>
                <td class="uas-bio-l1">Konsentrasi Keahlian</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasKonsentrasi }}</td>
                <td class="uas-bio-l2">Semester</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $semester }}</td>
            </tr>
        </table>
        <div class="uas-line-bottom"></div>

        <div class="uas-section-heading">A. Nilai Akademik</div>

        <table class="uas-grade-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 192px;">Mata Pelajaran</th>
                    <th style="width: 52px;">Nilai<br>Akhir</th>
                    <th>Capaian Pembelajaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gradeItems as $idx => $g)
                    @php $grpUas = $g['kelompok'] ?? 'umum'; @endphp
                    @if($distinctGroupsUas > 1 && $grpUas !== $lastGroupUas)
                        <tr>
                            <td colspan="4" class="uas-group-row">
                                {{ $g['kelompok_heading'] ?? ($uasGroupTitles[$grpUas] ?? strtoupper($g['kelompok_label'] ?? $grpUas)) }}
                            </td>
                        </tr>
                        @php $lastGroupUas = $grpUas; @endphp
                    @endif
                    <tr>
                        <td class="text-center" style="vertical-align: middle;">{{ $idx + 1 }}.</td>
                        <td style="vertical-align: middle;">{{ $g['nama'] }}</td>
                        <td class="text-center font-bold" style="vertical-align: middle;">
                            {{ $g['nilai_rapor'] !== null ? number_format($g['nilai_rapor'], 1) : '-' }}
                        </td>
                        <td class="uas-capaian-cell">{{ $g['deskripsi'] }}</td>
                    </tr>
                @endforeach

                <tr class="uas-summary-row">
                    <td colspan="2" style="font-weight: bold;">JUMLAH NILAI</td>
                    <td class="text-center font-bold">{{ $totalNilai > 0 ? number_format($totalNilai, 1) : '-' }}</td>
                    <td></td>
                </tr>
                <tr class="uas-summary-row">
                    <td colspan="2" style="font-weight: bold;">RATA-RATA NILAI</td>
                    <td class="text-center font-bold">{{ $rataRata !== null ? number_format($rataRata, 1) : '-' }}</td>
                    <td style="font-size: 7.8pt; font-weight: normal;">
                        @if($showRanking)
                            @if($stsSummary?->rank)
                                <strong>Peringkat ke-{{ $stsSummary->rank }}</strong> dari {{ $totalSiswaKelas }} siswa ({{ $displayClass }})
                            @endif
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ==================== HALAMAN 2: EKSKUL, KEHADIRAN, CATATAN & PENGESAHAN ==================== -->
    <div class="uas-page uas-page-2">
        <!-- Identitas Siswa Halaman 2 -->
        <table class="uas-biodata">
            <tr>
                <td class="uas-bio-l1">Nama Siswa</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasNamaSiswa }}</td>
                <td class="uas-bio-l2">Kelas</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $displayClass }}</td>
            </tr>
            <tr>
                <td class="uas-bio-l1">NIS / NISN</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasNisNisn }}</td>
                <td class="uas-bio-l2">Fase</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $uasFase }}</td>
            </tr>
            <tr>
                <td class="uas-bio-l1">Konsentrasi Keahlian</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v1">{{ $uasKonsentrasi }}</td>
                <td class="uas-bio-l2">Semester</td>
                <td class="uas-bio-col">:</td>
                <td class="uas-bio-v2">{{ $semester }}</td>
            </tr>
        </table>
        <div class="uas-line-bottom" style="margin-bottom: 14px;"></div>

        <!-- B. Ekstrakurikuler -->
        <div class="uas-section-heading">B. Ekstrakurikuler</div>
        <table class="uas-simple-table" style="margin-bottom: 14px;">
            <thead>
                <tr>
                    <th style="width: 34px; text-align: center;">No.</th>
                    <th>Kegiatan Ekstra Kurikuler</th>
                    <th style="width: 34%; text-align: center;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ekskulList as $ekIdx => $ek)
                    <tr>
                        <td class="text-center">{{ $ekIdx + 1 }}</td>
                        <td>{{ $ek['nama'] }}</td>
                        <td class="text-center">{{ $ek['keterangan'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- C. Ketidakhadiran -->
        <div class="uas-section-heading">C. Ketidakhadiran</div>
        <table class="uas-simple-table uas-absen-table" style="width: 38%; margin-bottom: 16px;">
            <tbody>
                <tr>
                    <td style="width: 62%;">Sakit</td>
                    <td class="text-center">{{ ($attendance->s ?? 0) > 0 ? $attendance->s : '-' }}</td>
                </tr>
                <tr>
                    <td>Izin</td>
                    <td class="text-center">{{ ($attendance->i ?? 0) > 0 ? $attendance->i : '-' }}</td>
                </tr>
                <tr>
                    <td>Tanpa Keterangan</td>
                    <td class="text-center">{{ ($attendance->a ?? 0) > 0 ? $attendance->a : '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- D. Catatan Wali Kelas -->
        <div class="uas-section-heading">D. Catatan Wali Kelas</div>
        <div class="uas-catatan-box">
            @if(!empty($stsSummary?->catatan_wali_kelas))
                "{{ $stsSummary->catatan_wali_kelas }}"
            @elseif($rataRata && $rataRata >= 85)
                "Prestasi belajar sangat memuaskan. Pertahankan semangat belajar, ketekunan, dan akhlak mulia dalam setiap kegiatan di sekolah."
            @elseif($rataRata && $rataRata >= 75)
                "Pencapaian belajar sudah baik. Tingkatkan terus kedisiplinan dan konsistensi dalam menyelesaikan tugas-tugas akademik."
            @else
                "Tingkatkan motivasi dan waktu belajar di rumah. Jangan ragu berdiskusi dengan guru pengampu jika menemui kesulitan materi."
            @endif
        </div>

        <!-- Blok Tanda Tangan & Keputusan -->
        <table class="uas-bottom-grid">
            <tr>
                <td class="uas-bottom-left">
                    <div style="font-weight: bold;">Wali Kelas</div>
                    <div class="uas-ttd-space">
                        @if(!empty($itemHomeroom?->ttd_wali_kelas))
                            <img src="{{ asset('storage/' . $itemHomeroom->ttd_wali_kelas) }}" alt="TTD Wali Kelas" style="max-height: 48px; max-width: 135px; object-fit: contain;">
                        @endif
                    </div>
                    <div style="font-weight: bold; text-decoration: underline;">{{ $waliNama }}</div>
                    <div>Nip. {{ $waliNip }}</div>

                    <div style="margin-top: 22px;">
                        <div>Orang Tua/Wali,</div>
                        <div class="uas-ttd-space"></div>
                        <div style="width: 145px; border-bottom: 1px solid #000;"></div>
                    </div>
                </td>
                <td class="uas-bottom-right">
                    <div class="uas-keputusan-box">
                        <div style="font-weight: bold; margin-bottom: 8px;">Keputusan :</div>
                        <div>Berdasarkan hasil yang dicapai pada semester 1 dan 2,</div>
                        <div style="margin-bottom: 4px;">Peserta Didik ditetapkan :</div>

                        @if($kepStatus === 'lulus' || $kepStatus === 'tidak_lulus')
                            <div style="font-weight: bold; margin: 4px 0 6px 0;">
                                {{ $kepLulusLabel ?? ($kepStatus === 'lulus' ? 'Lulus' : 'Tidak Lulus') }}
                            </div>
                        @else
                            <table class="uas-keputusan-rows">
                                <tr>
                                    <td style="width: 96px; font-weight: bold;">Naik ke kelas</td>
                                    <td>{{ $kepNaikKe }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold;">Tinggal di kelas</td>
                                    <td>{{ $kepTinggalDi }}</td>
                                </tr>
                            </table>
                        @endif

                        <div class="uas-kepsek-sign">
                            <div>{{ $kotaPembagian }}, {{ $itemTanggal }}</div>
                            <div>Kepala Sekolah</div>
                            <div class="uas-ttd-space" style="position: relative;">
                                @if(!empty($itemRaporSettings?->ttd_kepala_sekolah))
                                    <img src="{{ asset('storage/' . $itemRaporSettings->ttd_kepala_sekolah) }}" alt="TTD Kepala Sekolah" style="max-height: 48px; max-width: 135px; object-fit: contain;">
                                @endif
                                @if(!empty($itemRaporSettings?->stempel_sekolah))
                                    <img src="{{ asset('storage/' . $itemRaporSettings->stempel_sekolah) }}" alt="Stempel" style="max-height: 52px; position: absolute; left: -24px; top: -4px; opacity: 0.85; pointer-events: none;">
                                @endif
                            </div>
                            <div style="font-weight: bold; text-decoration: underline;">{{ $kepsekNama }}</div>
                            <div>Nip. {{ $kepsekNip }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        @if(!empty($itemArchive) && !empty($itemQrSvg))
            <div class="footer-keep-together" style="margin-top: 16px; border-top: 1px dashed #64748b; padding-top: 8px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
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
</div>
