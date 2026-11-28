<?php

namespace App\Services;

use App\Http\Controllers\Erapor\CetakRaporController;
use App\Models\AbsensiSiswa;
use App\Models\AssessmentItem;
use App\Models\ClassHomeroom;
use App\Models\EraporAuditLog;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporArchive;
use App\Models\RaporSetting;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RaporArchiveService
{
    public function __construct(
        protected GradingCalculationService $gradingService
    ) {}

    /**
     * Resolve the student's authentic historical class for a given academic year & semester.
     */
    public function resolveHistoricalClass(Siswa $siswa, int $tahunAjaranId, string $semester): ?string
    {
        $isGenericAlumniLabel = static function (?string $kelas): bool {
            $clean = strtolower(trim((string) $kelas));
            return $clean === '' || str_starts_with($clean, 'alumni') || str_starts_with($clean, 'lulus');
        };

        $exact = RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->first();
        if ($exact && !$isGenericAlumniLabel($exact->kelas)) {
            return $exact->kelas;
        }

        $sameYear = RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->get()
            ->first(fn ($rw) => !$isGenericAlumniLabel($rw->kelas));
        if ($sameYear) {
            return $sameYear->kelas;
        }

        $fromGrade = SubjectGradeSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->whereNotNull('kelas')
            ->value('kelas');
        if (!$isGenericAlumniLabel($fromGrade)) {
            return $fromGrade;
        }

        $fromArchive = RaporArchive::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->value('kelas');
        if (!empty($fromArchive)) {
            return $fromArchive;
        }

        return $siswa->kelas;
    }

    /**
     * Resolve all student IDs who belonged to a class during an academic year & semester.
     */
    public function resolveCohortStudentIds(string $kelas, int $tahunAjaranId, string $semester): Collection
    {
        $exactIds = RiwayatKelasSiswa::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->pluck('siswa_id');

        if ($exactIds->isNotEmpty()) {
            return $exactIds->unique()->values();
        }

        $yearIds = RiwayatKelasSiswa::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->pluck('siswa_id');

        if ($yearIds->isNotEmpty()) {
            return $yearIds->unique()->values();
        }

        return Siswa::where('kelas', $kelas)->pluck('id');
    }

    /**
     * Resolve distinct non-alumni classes for an academic year & semester.
     */
    public function resolveClassesForPeriod(int $tahunAjaranId, string $semester): Collection
    {
        $historical = RiwayatKelasSiswa::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->distinct()
            ->pluck('kelas');

        $yearFallback = $historical->isEmpty()
            ? RiwayatKelasSiswa::where('tahun_ajaran_id', $tahunAjaranId)->distinct()->pluck('kelas')
            : collect();

        $master = Rombel::classNames($tahunAjaranId);

        return $historical
            ->merge($yearFallback)
            ->merge($master)
            ->map(fn ($c) => trim((string) $c))
            ->filter(function (string $c) {
                if ($c === '') {
                    return false;
                }
                $lower = strtolower($c);
                return !str_starts_with($lower, 'alumni') && !str_starts_with($lower, 'lulus');
            })
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Resolve semester-specific attendance date range within an academic year.
     */
    public function resolveSemesterDateRange(?TahunAjaran $year, string $semester): ?array
    {
        if (!$year?->tanggal_mulai || !$year?->tanggal_selesai) {
            return null;
        }

        $start = Carbon::parse($year->tanggal_mulai);
        $end = Carbon::parse($year->tanggal_selesai);

        // Typical Indonesian academic year spans Jul (year N) - Jun (year N+1)
        if ($start->year < $end->year) {
            if ($semester === 'Ganjil') {
                $semStart = $start->copy();
                $semEnd = Carbon::create($start->year, 12, 31)->min($end);
                return [$semStart->toDateString(), $semEnd->toDateString()];
            }

            $semStart = Carbon::create($end->year, 1, 1)->max($start);
            $semEnd = $end->copy();
            return [$semStart->toDateString(), $semEnd->toDateString()];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * Build the full canonical report card payload from live tables (using historical class).
     */
    public function buildLiveReportData(
        Siswa $siswa,
        int $tahunAjaranId,
        string $semester,
        string $jenisRapor,
        bool $recalculate = true
    ): array {
        $jenisRapor = ($jenisRapor === 'akhir') ? 'uas' : $jenisRapor;
        $targetYear = TahunAjaran::find($tahunAjaranId)
            ?? TahunAjaran::where('is_aktif', true)->first()
            ?? TahunAjaran::first();

        $kelasHistoris = $this->resolveHistoricalClass($siswa, $tahunAjaranId, $semester) ?? ($siswa->kelas ?? '-');
        $cohortIds = $this->resolveCohortStudentIds($kelasHistoris, $tahunAjaranId, $semester);
        if (!$cohortIds->contains($siswa->id)) {
            $cohortIds = $cohortIds->push($siswa->id)->unique()->values();
        }

        if ($recalculate && $kelasHistoris !== '') {
            $this->gradingService->recalculateClassSTS($kelasHistoris, $tahunAjaranId, $semester);
        }

        $schoolSettings = PengaturanSekolah::first();
        $raporSettings = RaporSetting::instance();
        $rombel = Rombel::with('jurusan')
            ->where('nama', $kelasHistoris)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first()
            ?? Rombel::with('jurusan')
                ->where('nama', $kelasHistoris)
                ->orderByDesc('tahun_ajaran_id')
                ->first();
        $classJurusan = $rombel?->jurusan ?? Jurusan::resolveFromRombelName($kelasHistoris);
        $inferredMeta = Rombel::inferMetaFromName((string) $kelasHistoris);
        $tingkat = $rombel?->tingkat ?: $inferredMeta['tingkat'];
        $konsentrasiKeahlian = self::resolveKonsentrasiKeahlian($rombel, $classJurusan, $inferredMeta['kompetensi_keahlian'] ?? null);
        $resolvedFase = self::resolveFase($kelasHistoris, $tingkat, $raporSettings->fase_text);
        $subjects = MataPelajaran::forClass($kelasHistoris, $tahunAjaranId, $semester, $siswa->id);

        $summaries = SubjectGradeSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('mata_pelajaran_id');

        $stsSummary = StudentStsSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->first();

        $attQuery = AbsensiSiswa::where('siswa_id', $siswa->id);
        $dateRange = $this->resolveSemesterDateRange($targetYear, $semester);
        if ($dateRange !== null) {
            $attQuery->whereBetween('tanggal', $dateRange);
        }
        $attendance = $attQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        $homeroom = ClassHomeroom::where('kelas', $kelasHistoris)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('guru')
            ->first();

        $waliKelas = $homeroom?->guru ?? Jadwal::where('kelas', $kelasHistoris)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('guru')
            ->first()?->guru;

        $kepalaSekolah = User::where('role', 'kepala_sekolah')->with('guru')->first();
        $tanggalRaporFormatted = $raporSettings->tanggal_rapor
            ? $raporSettings->tanggal_rapor->translatedFormat('d F Y')
            : date('d F Y');

        $jadwalGuruMap = [];
        $classJadwals = Jadwal::where('kelas', $kelasHistoris)
            ->with('guru')
            ->orderByRaw('tahun_ajaran_id = ? DESC', [$tahunAjaranId])
            ->get();
        foreach ($classJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }
        $fallbackJadwals = Jadwal::with('guru')->orderBy('id', 'desc')->get();
        foreach ($fallbackJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }

        $assessmentItems = AssessmentItem::with('component')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get();
        $studentScoresByItem = StudentAssessmentScore::where('siswa_id', $siswa->id)
            ->whereIn('assessment_item_id', $assessmentItems->pluck('id'))
            ->get()
            ->keyBy('assessment_item_id');
        $itemsBySubject = $assessmentItems->groupBy('mata_pelajaran_id');

        $gradeItems = [];
        $totalNilai = 0.0;
        $filledCount = 0;

        foreach ($subjects as $mapel) {
            $sum = $summaries->get($mapel->id);
            $subjectItems = $itemsBySubject->get($mapel->id, collect());

            $uhScores = [1 => null, 2 => null, 3 => null];
            $tugasScores = [1 => null, 2 => null, 3 => null];
            $praktekScores = [1 => null, 2 => null];
            $projekScores = [1 => null, 2 => null];
            $portofolioScores = [1 => null, 2 => null];
            $stsItemScore = null;

            foreach ($subjectItems as $item) {
                $code = strtolower($item->component?->code ?? '');
                if ($code === '' && !empty($item->title)) {
                    $tLower = strtolower($item->title);
                    if (str_starts_with($tLower, 'tugas')) $code = 'tugas';
                    elseif (str_starts_with($tLower, 'uh') || str_contains($tLower, 'ulangan')) $code = 'uh';
                    elseif (str_starts_with($tLower, 'praktek') || str_starts_with($tLower, 'praktik')) $code = 'praktek';
                    elseif (str_starts_with($tLower, 'projek') || str_starts_with($tLower, 'proyek')) $code = 'projek';
                    elseif (str_starts_with($tLower, 'portofolio')) $code = 'portofolio';
                    elseif (str_starts_with($tLower, 'sts') || str_starts_with($tLower, 'pts')) $code = 'sts';
                }

                $seq = (int) $item->sequence;
                if ($seq <= 0 && preg_match('/\d+/', $item->title, $m)) {
                    $seq = (int) $m[0];
                }

                $val = $studentScoresByItem->get($item->id)?->score;
                if ($val === null) {
                    continue;
                }

                if ($code === 'uh') {
                    $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                    if ($targetSeq === null || $uhScores[$targetSeq] !== null) {
                        foreach ($uhScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $uhScores)) {
                        $uhScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'tugas') {
                    $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                    if ($targetSeq === null || $tugasScores[$targetSeq] !== null) {
                        foreach ($tugasScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $tugasScores)) {
                        $tugasScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'praktek') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $praktekScores[$targetSeq] !== null) {
                        foreach ($praktekScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $praktekScores)) {
                        $praktekScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'projek') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $projekScores[$targetSeq] !== null) {
                        foreach ($projekScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $projekScores)) {
                        $projekScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'portofolio') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $portofolioScores[$targetSeq] !== null) {
                        foreach ($portofolioScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $portofolioScores)) {
                        $portofolioScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'sts' || $code === 'asat') {
                    $stsItemScore = (float) $val;
                }
            }

            if (empty(array_filter($uhScores, fn ($v) => $v !== null)) && $sum?->uh_avg !== null) {
                $uhScores[1] = (float) $sum->uh_avg;
            }
            if (empty(array_filter($tugasScores, fn ($v) => $v !== null)) && $sum?->tugas_avg !== null) {
                $tugasScores[1] = (float) $sum->tugas_avg;
            }
            if (empty(array_filter($praktekScores, fn ($v) => $v !== null)) && $sum?->praktek_avg !== null) {
                $praktekScores[1] = (float) $sum->praktek_avg;
            }
            if (empty(array_filter($projekScores, fn ($v) => $v !== null)) && $sum?->projek_avg !== null) {
                $projekScores[1] = (float) $sum->projek_avg;
            }
            if (empty(array_filter($portofolioScores, fn ($v) => $v !== null)) && $sum?->portofolio_avg !== null) {
                $portofolioScores[1] = (float) $sum->portofolio_avg;
            }

            $formatif = null;
            if ($sum) {
                $components = array_filter([$sum->uh_avg, $sum->tugas_avg, $sum->praktek_avg, $sum->projek_avg, $sum->portofolio_avg], fn ($v) => $v !== null);
                if (!empty($components)) {
                    $formatif = round(array_sum($components) / count($components), 1);
                }
            }

            $asatScore = $sum?->asat_score ?? $stsItemScore ?? $sum?->sts_score;
            $sumatif = $asatScore;
            $nilaiRapor = $sum?->final_score ?? $asatScore ?? $formatif;
            $nilaiSts = $asatScore ?? $nilaiRapor;
            $acuanSkor = ($jenisRapor === 'sts') ? $nilaiSts : $nilaiRapor;

            if ($acuanSkor !== null) {
                $totalNilai = round($totalNilai + (float) $acuanSkor, 1);
                $filledCount++;
            }

            $predikat = CetakRaporController::calculatePredikat($acuanSkor);
            $deskripsi = !empty($sum?->capaian_kompetensi)
                ? $sum->capaian_kompetensi
                : CetakRaporController::generateDeskripsi($mapel->nama, $acuanSkor, $predikat);

            $gradeItems[] = [
                'nama' => $mapel->nama,
                'guru_nama' => $jadwalGuruMap[$mapel->id] ?? null,
                'kode' => $mapel->kode ?? '-',
                'kelompok' => $mapel->kelompok ?? 'umum',
                'kelompok_label' => $mapel->kelompok_label,
                'kelompok_heading' => MataPelajaran::resolveKelompokHeading($mapel->kelompok ?? 'umum', $classJurusan, $mapel->jurusan),
                'kkm' => $homeroom
                    ? $homeroom->getKkmForSubject($mapel->id, $mapel->kkm ?? $raporSettings->default_kkm ?? 75)
                    : ($mapel->kkm !== null ? (float) $mapel->kkm : (float) ($raporSettings->default_kkm ?? 75)),
                'uh' => $uhScores,
                'tugas' => $tugasScores,
                'praktek' => $praktekScores,
                'projek' => $projekScores,
                'portofolio' => $portofolioScores,
                'daily_scores' => $sum?->daily_scores ?? [],
                'sh_avg' => $sum?->sh_avg,
                'asat_score' => $asatScore,
                'formatif' => $formatif,
                'sumatif' => $sumatif,
                'nilai_sts' => $nilaiSts,
                'nilai_rapor' => $nilaiRapor,
                'predikat' => $predikat,
                'deskripsi' => $deskripsi,
            ];
        }

        $rataRata = $filledCount > 0 ? round($totalNilai / $filledCount, 1) : null;
        $totalSiswaKelas = $cohortIds->count();

        $classAvgRaw = StudentStsSummary::whereIn('siswa_id', $cohortIds)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->whereNotNull('average_score')
            ->avg('average_score');
        $rataRataKelas = $classAvgRaw !== null ? round((float) $classAvgRaw, 1) : $rataRata;

        $ekstrakurikuler = self::normalizeEkstrakurikuler($stsSummary?->ekstrakurikuler);
        $keputusan = self::resolveKeputusan(
            $tingkat,
            $semester,
            $stsSummary?->keputusan_status,
            $stsSummary?->keputusan_kelas
        );

        return [
            'student' => [
                'id' => $siswa->id,
                'nama_lengkap' => $siswa->nama_lengkap,
                'nipd' => $siswa->nipd,
                'nisn' => $siswa->nisn,
                'kelas' => $kelasHistoris,
                'tingkat' => $tingkat,
                'fase' => $resolvedFase,
                'kompetensi_keahlian' => $konsentrasiKeahlian,
                'konsentrasi_keahlian' => $konsentrasiKeahlian,
            ],
            'period' => [
                'tahun_ajaran_id' => $targetYear?->id ?? $tahunAjaranId,
                'tahun_ajaran_nama' => $targetYear?->nama ?? '-',
                'semester' => $semester,
                'jenis_rapor' => $jenisRapor,
                'rombel_id' => $rombel?->id ?? $siswa->rombel_id,
            ],
            'school' => [
                'nama_sekolah' => $schoolSettings?->nama_sekolah ?? 'SMKS PGRI 5 Serpong',
                'npsn' => $schoolSettings?->npsn,
                'alamat_sekolah' => $schoolSettings?->alamat_sekolah ?? '',
                'no_telepon_sekolah' => $schoolSettings?->no_telepon_sekolah ?? '',
                'email_sekolah' => $schoolSettings?->email_sekolah ?? '',
                'logo' => $schoolSettings?->logo,
                'logo_yayasan' => $raporSettings->logo_yayasan,
                'header_yayasan' => $raporSettings->header_yayasan,
                'header_sekolah' => $raporSettings->header_sekolah ?? ($schoolSettings?->nama_sekolah ?? 'SMKS PGRI 5 SERPONG'),
            ],
            'format' => [
                'judul_rapor' => $raporSettings->getEffectiveJudulRapor($jenisRapor),
                'nama_kurikulum' => $raporSettings->nama_kurikulum ?? 'Kurikulum Merdeka',
                'show_kurikulum' => (bool) ($raporSettings->show_kurikulum ?? true),
                'show_fase' => (bool) ($raporSettings->show_fase ?? false),
                'fase_text' => $raporSettings->fase_text ?: 'Fase E',
                'ukuran_kertas' => $raporSettings->ukuran_kertas ?? 'A4',
                'show_ranking' => (bool) ($raporSettings->show_ranking ?? true),
                'show_kkm' => (bool) ($raporSettings->show_kkm ?? false),
                'kota_pembagian' => $raporSettings->kota_pembagian ?? 'Kota Tangerang Selatan',
                'tanggal_rapor_formatted' => $tanggalRaporFormatted,
            ],
            'signatories' => [
                'wali_kelas_nama' => $homeroom?->effective_nama ?? $waliKelas?->nama_lengkap ?? 'Wali Kelas',
                'wali_kelas_nip' => $homeroom?->effective_nip ?? $waliKelas?->nip ?? '-',
                'ttd_wali_kelas' => $homeroom?->ttd_wali_kelas,
                'kepala_sekolah_nama' => $raporSettings->nama_kepala_sekolah ?: ($kepalaSekolah?->name ?? 'Kepala Sekolah'),
                'kepala_sekolah_nip' => $raporSettings->nip_kepala_sekolah ?: ($kepalaSekolah?->guru?->nip ?? '-'),
                'ttd_kepala_sekolah' => $raporSettings->ttd_kepala_sekolah,
                'stempel_sekolah' => $raporSettings->stempel_sekolah,
            ],
            'grades' => $gradeItems,
            'summary' => [
                'total_nilai' => $totalNilai,
                'rata_rata' => $rataRata,
                'rata_rata_kelas' => $rataRataKelas,
                'rank' => $stsSummary?->rank,
                'total_siswa_kelas' => $totalSiswaKelas,
                'catatan_wali_kelas' => $stsSummary?->catatan_wali_kelas,
                'ekstrakurikuler' => $ekstrakurikuler,
                'keputusan' => $keputusan,
                'filled_count' => $filledCount,
            ],
            'attendance' => [
                's' => (int) ($attendance->s ?? 0),
                'i' => (int) ($attendance->i ?? 0),
                'a' => (int) ($attendance->a ?? 0),
            ],
        ];
    }

    /**
     * Freeze a student's report card into an immutable RaporArchive snapshot.
     */
    public function archiveStudent(
        Siswa $siswa,
        int $tahunAjaranId,
        string $semester,
        string $jenisRapor,
        ?int $userId = null,
        bool $recalculate = true
    ): RaporArchive {
        $jenisRapor = ($jenisRapor === 'akhir') ? 'uas' : $jenisRapor;
        $payload = $this->buildLiveReportData($siswa, $tahunAjaranId, $semester, $jenisRapor, $recalculate);
        $hash = RaporArchive::computeHash($payload);

        $existing = RaporArchive::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->first();

        $kelas = $payload['student']['kelas'];
        $rombelId = $payload['period']['rombel_id'];

        if ($existing) {
            if ($existing->document_hash === $hash) {
                return $existing;
            }

            $existing->update([
                'kelas' => $kelas,
                'rombel_id' => $rombelId,
                'document_hash' => $hash,
                'version' => $existing->version + 1,
                'payload' => $payload,
                'finalized_by' => $userId ?? $existing->finalized_by,
                'finalized_at' => now(),
            ]);

            return $existing->fresh();
        }

        $taSlug = str_replace(['/', ' '], '-', (string) $payload['period']['tahun_ajaran_nama']);
        $semSlug = strtolower($semester) === 'genap' ? 'GNP' : 'GJL';
        $jenisSlug = strtoupper($jenisRapor);
        $kelasSlug = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $kelas));
        $idSlug = strtoupper((string) preg_replace('/[^A-Za-z0-9._-]+/', '', (string) ($siswa->nipd ?: ($siswa->nisn ?: $siswa->id))));

        $baseArchiveNumber = "RPR/{$taSlug}/{$semSlug}/{$jenisSlug}/{$kelasSlug}/{$idSlug}";
        $archiveNumber = $baseArchiveNumber;
        $suffix = 1;
        while (RaporArchive::where('archive_number', $archiveNumber)->exists()) {
            $archiveNumber = "{$baseArchiveNumber}-{$suffix}";
            $suffix++;
        }

        return RaporArchive::create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $tahunAjaranId,
            'semester' => $semester,
            'jenis_rapor' => $jenisRapor,
            'kelas' => $kelas,
            'rombel_id' => $rombelId,
            'archive_number' => $archiveNumber,
            'verification_token' => (string) Str::uuid(),
            'document_hash' => $hash,
            'version' => 1,
            'payload' => $payload,
            'finalized_by' => $userId,
            'finalized_at' => now(),
        ]);
    }

    /**
     * Archive report cards for every student in a class for the given period.
     */
    public function archiveClass(
        string $kelas,
        int $tahunAjaranId,
        string $semester,
        array $jenisList = ['sts', 'uas'],
        ?int $userId = null
    ): int {
        $this->gradingService->recalculateClassSTS($kelas, $tahunAjaranId, $semester);

        $studentIds = $this->resolveCohortStudentIds($kelas, $tahunAjaranId, $semester);
        $students = Siswa::whereIn('id', $studentIds)->orderBy('nama_lengkap')->get();

        $archivedCount = 0;
        foreach ($students as $siswa) {
            foreach ($jenisList as $jenis) {
                $this->archiveStudent($siswa, $tahunAjaranId, $semester, $jenis, $userId, recalculate: false);
                $archivedCount++;
            }
        }

        EraporAuditLog::record(
            action: 'archive',
            entityType: 'RaporArchive',
            entityId: "{$kelas}_{$tahunAjaranId}_{$semester}",
            before: null,
            after: [
                'kelas' => $kelas,
                'semester' => $semester,
                'jenis_list' => $jenisList,
                'archived_count' => $archivedCount,
            ],
            reason: "Pengarsipan permanen rapor digital kelas {$kelas} ({$semester})"
        );

        return $archivedCount;
    }

    /**
     * Resolve view-ready print data, preferring the immutable RaporArchive snapshot when available.
     */
    public function resolveViewDataForPrint(
        Siswa $siswa,
        int $tahunAjaranId,
        string $semester,
        string $jenisRapor,
        bool $preferArchive = true,
        bool $trackReprint = true
    ): array {
        $jenisRapor = ($jenisRapor === 'akhir') ? 'uas' : $jenisRapor;

        $archive = null;
        if ($preferArchive) {
            $archive = RaporArchive::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->where('jenis_rapor', $jenisRapor)
                ->first();
        }

        if ($archive && is_array($archive->payload)) {
            if ($trackReprint) {
                $archive->increment('reprint_count');
                $archive->forceFill(['last_reprinted_at' => now()])->save();
            }

            $payload = $archive->payload;
            $verifyUrl = route('rapor.verify', $archive->verification_token);
            $qrSvg = QrCodeSvgService::svg($verifyUrl, 74);

            return $this->hydrateViewDataFromPayload($siswa, $payload, $archive, $qrSvg, $verifyUrl);
        }

        $payload = $this->buildLiveReportData($siswa, $tahunAjaranId, $semester, $jenisRapor, recalculate: true);

        return $this->hydrateViewDataFromPayload($siswa, $payload, null, null, null);
    }

    /**
     * Hydrate Blade view data uniformly from either a frozen snapshot payload or a live payload.
     */
    protected function hydrateViewDataFromPayload(
        Siswa $siswa,
        array $payload,
        ?RaporArchive $archive,
        ?string $qrSvg,
        ?string $verifyUrl
    ): array {
        $period = $payload['period'];
        $student = $payload['student'];
        $school = $payload['school'];
        $format = $payload['format'];
        $signatories = $payload['signatories'];
        $summary = $payload['summary'];
        $att = $payload['attendance'];

        $targetYear = TahunAjaran::find($period['tahun_ajaran_id']) ?? (object) [
            'id' => $period['tahun_ajaran_id'],
            'nama' => $period['tahun_ajaran_nama'],
        ];

        $schoolSettings = (object) $school;
        $raporSettings = new RaporSetting([
            'nama_kurikulum' => $format['nama_kurikulum'],
            'tipe_judul_rapor' => 'custom',
            'judul_rapor_sts' => $format['judul_rapor'],
            'judul_rapor_akhir' => $format['judul_rapor'],
            'show_kurikulum' => $format['show_kurikulum'],
            'show_fase' => $format['show_fase'],
            'fase_text' => $format['fase_text'],
            'ukuran_kertas' => $format['ukuran_kertas'],
            'show_ranking' => $format['show_ranking'],
            'show_kkm' => $format['show_kkm'],
            'kota_pembagian' => $format['kota_pembagian'],
            'nama_kepala_sekolah' => $signatories['kepala_sekolah_nama'],
            'nip_kepala_sekolah' => $signatories['kepala_sekolah_nip'],
            'ttd_kepala_sekolah' => $signatories['ttd_kepala_sekolah'],
            'stempel_sekolah' => $signatories['stempel_sekolah'],
            'logo_yayasan' => $school['logo_yayasan'] ?? null,
            'header_yayasan' => $school['header_yayasan'],
            'header_sekolah' => $school['header_sekolah'],
        ]);

        $homeroom = (object) [
            'effective_nama' => $signatories['wali_kelas_nama'],
            'effective_nip' => $signatories['wali_kelas_nip'],
            'ttd_wali_kelas' => $signatories['ttd_wali_kelas'],
        ];

        $inferredMeta = Rombel::inferMetaFromName((string) ($student['kelas'] ?? ''));
        $tingkat = $student['tingkat'] ?? $inferredMeta['tingkat'];
        $resolvedFase = $student['fase'] ?? self::resolveFase($student['kelas'] ?? null, $tingkat, $format['fase_text'] ?? null);
        $konsentrasiKeahlian = $student['konsentrasi_keahlian']
            ?? $student['kompetensi_keahlian']
            ?? $inferredMeta['kompetensi_keahlian']
            ?? Jurusan::resolveFromRombelName((string) ($student['kelas'] ?? ''))?->nama
            ?? '-';
        $ekstrakurikuler = self::normalizeEkstrakurikuler($summary['ekstrakurikuler'] ?? null);
        $keputusan = $summary['keputusan'] ?? self::resolveKeputusan($tingkat, $period['semester'], null, null);

        $stsSummary = (object) [
            'rank' => $summary['rank'],
            'catatan_wali_kelas' => $summary['catatan_wali_kelas'],
            'ekstrakurikuler' => $ekstrakurikuler,
            'keputusan' => $keputusan,
        ];

        $attendance = (object) $att;

        return [
            'siswa' => $siswa,
            'studentData' => $student,
            'kelasHistoris' => $student['kelas'],
            'resolvedFase' => $resolvedFase,
            'konsentrasiKeahlian' => $konsentrasiKeahlian,
            'ekstrakurikuler' => $ekstrakurikuler,
            'keputusan' => $keputusan,
            'targetYear' => $targetYear,
            'semester' => $period['semester'],
            'jenisRapor' => $period['jenis_rapor'],
            'schoolSettings' => $schoolSettings,
            'schoolData' => $school,
            'raporSettings' => $raporSettings,
            'formatData' => $format,
            'signatoriesData' => $signatories,
            'showKkm' => (bool) ($format['show_kkm'] ?? false),
            'homeroom' => $homeroom,
            'tanggalRaporFormatted' => $format['tanggal_rapor_formatted'],
            'gradeItems' => $payload['grades'],
            'totalNilai' => $summary['total_nilai'],
            'rataRata' => $summary['rata_rata'],
            'rataRataKelas' => $summary['rata_rata_kelas'],
            'stsSummary' => $stsSummary,
            'attendance' => $attendance,
            'waliKelas' => (object) ['nama_lengkap' => $signatories['wali_kelas_nama'], 'nip' => $signatories['wali_kelas_nip']],
            'kepalaSekolah' => (object) ['name' => $signatories['kepala_sekolah_nama']],
            'totalSiswaKelas' => $summary['total_siswa_kelas'],
            'archive' => $archive,
            'isArchived' => $archive !== null,
            'qrSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
        ];
    }

    public static function resolveFase(?string $kelas, ?string $tingkat, ?string $fallbackFaseText): string
    {
        $cleanTingkat = strtoupper(trim((string) $tingkat));
        if ($cleanTingkat === '' && !empty($kelas)) {
            $cleanTingkat = strtoupper((string) (Rombel::inferMetaFromName($kelas)['tingkat'] ?? ''));
        }

        if ($cleanTingkat === 'X' || $cleanTingkat === '10') {
            return 'E';
        }
        if (in_array($cleanTingkat, ['XI', 'XII', '11', '12'], true)) {
            return 'F';
        }

        $fallback = trim((string) $fallbackFaseText);
        if ($fallback !== '') {
            return trim(preg_replace('/^fase\s+/i', '', $fallback));
        }

        return '-';
    }

    public static function resolveKonsentrasiKeahlian(?Rombel $rombel, ?Jurusan $jurusan, ?string $inferred): string
    {
        $raw = trim((string) ($rombel?->kompetensi_keahlian ?: $jurusan?->nama ?: $inferred ?: ''));
        return $raw !== '' ? $raw : '-';
    }

    public static function normalizeEkstrakurikuler($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $nama = trim((string) ($row['nama'] ?? ''));
            $ket = trim((string) ($row['keterangan'] ?? ''));
            if ($nama === '' && $ket === '') {
                continue;
            }
            $items[] = [
                'nama' => $nama !== '' ? $nama : '-',
                'keterangan' => $ket !== '' ? $ket : '-',
            ];
        }

        return array_slice($items, 0, 5);
    }

    public static function resolveKeputusan(
        ?string $tingkat,
        string $semester,
        ?string $overrideStatus,
        ?string $overrideKelas
    ): array {
        $cleanTingkat = strtoupper(trim((string) $tingkat));
        $status = $overrideStatus ? strtolower(trim($overrideStatus)) : null;
        $kelasText = $overrideKelas ? trim($overrideKelas) : null;

        if ($status === 'naik') {
            $defaultNext = match ($cleanTingkat) {
                'X', '10' => '11 (Sebelas)',
                'XI', '11' => '12 (Dua belas)',
                default => '-',
            };
            return [
                'status' => 'naik',
                'naik_ke' => $kelasText ?: $defaultNext,
                'tinggal_di' => '-',
                'label_lulus' => null,
            ];
        }

        if ($status === 'tinggal') {
            $defaultStay = match ($cleanTingkat) {
                'X', '10' => '10 (Sepuluh)',
                'XI', '11' => '11 (Sebelas)',
                'XII', '12' => '12 (Dua belas)',
                default => $cleanTingkat ?: '-',
            };
            return [
                'status' => 'tinggal',
                'naik_ke' => '-',
                'tinggal_di' => $kelasText ?: $defaultStay,
                'label_lulus' => null,
            ];
        }

        if ($status === 'lulus') {
            return [
                'status' => 'lulus',
                'naik_ke' => '-',
                'tinggal_di' => '-',
                'label_lulus' => $kelasText ?: 'Lulus',
            ];
        }

        if ($status === 'tidak_lulus') {
            return [
                'status' => 'tidak_lulus',
                'naik_ke' => '-',
                'tinggal_di' => '-',
                'label_lulus' => $kelasText ?: 'Tidak Lulus',
            ];
        }

        if (strtolower(trim($semester)) === 'genap') {
            if ($cleanTingkat === 'X' || $cleanTingkat === '10') {
                return [
                    'status' => 'naik',
                    'naik_ke' => '11 (Sebelas)',
                    'tinggal_di' => '-',
                    'label_lulus' => null,
                ];
            }
            if ($cleanTingkat === 'XI' || $cleanTingkat === '11') {
                return [
                    'status' => 'naik',
                    'naik_ke' => '12 (Dua belas)',
                    'tinggal_di' => '-',
                    'label_lulus' => null,
                ];
            }
            if ($cleanTingkat === 'XII' || $cleanTingkat === '12') {
                return [
                    'status' => 'lulus',
                    'naik_ke' => '-',
                    'tinggal_di' => '-',
                    'label_lulus' => 'Lulus',
                ];
            }
        }

        return [
            'status' => 'pending',
            'naik_ke' => '-',
            'tinggal_di' => '-',
            'label_lulus' => null,
        ];
    }
}
