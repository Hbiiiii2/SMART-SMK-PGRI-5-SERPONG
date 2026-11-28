<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\ClassHomeroom;
use App\Models\EraporAuditLog;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporSetting;
use App\Models\ReportFinalization;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\EraporExportService;
use App\Services\GradingCalculationService;
use App\Services\RaporArchiveService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecapStsController extends Controller
{
    public function __construct(
        protected GradingCalculationService $gradingService,
        protected EraporExportService $exportService
    ) {}

    protected function buildRecapData(Request $request, ?string $forcedKelas = null): array
    {
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? $allYears->first();
        $tahunAjaranId = (int) $request->get('tahun_ajaran_id', $activeYear?->id);
        $selectedYear = $allYears->firstWhere('id', $tahunAjaranId) ?? $activeYear;
        $semester = $request->get('semester', 'Ganjil');

        // Resolve available classes for this academic year & semester
        $historicalClasses = \App\Models\RiwayatKelasSiswa::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->distinct()
            ->pluck('kelas');

        if ($historicalClasses->isNotEmpty()) {
            $classes = $historicalClasses->filter(fn ($c) => !str_starts_with(strtolower($c), 'alumni') && !str_starts_with(strtolower($c), 'lulus'))->values();
        } else {
            $classes = Rombel::classNames($tahunAjaranId);
        }

        $selectedClass = $forcedKelas ?? $request->get('kelas', $classes->first());

        // Ensure calculations are up to date
        if ($selectedClass) {
            $this->gradingService->recalculateClassSTS($selectedClass, $tahunAjaranId, $semester);
        }

        $subjects = $selectedClass
            ? MataPelajaran::forClass($selectedClass, $tahunAjaranId, $semester)
            : MataPelajaran::sortSubjects(MataPelajaran::all());

        // Historical vs current student resolution
        $archiveService = app(RaporArchiveService::class);
        $cohortIds = $selectedClass
            ? $archiveService->resolveCohortStudentIds($selectedClass, $tahunAjaranId, $semester)
            : collect();
        $students = $cohortIds->isNotEmpty()
            ? Siswa::whereIn('id', $cohortIds)->orderBy('nama_lengkap')->get()
            : collect();

        $studentIds = $students->pluck('id');

        // Fetch scores per mapel
        $stsScores = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        // Fetch Summaries (Total, Avg, Rank)
        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $studentIds)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        // Fetch Attendance counts from SMART+ (Sakit, Izin, Alfa) scoped to semester dates
        $attendanceMap = [];
        $attQuery = AbsensiSiswa::whereIn('siswa_id', $studentIds);
        $dateRange = $archiveService->resolveSemesterDateRange($selectedYear, $semester);
        if ($dateRange !== null) {
            $attQuery->whereBetween('tanggal', $dateRange);
        }
        $attResults = $attQuery->selectRaw("
                siswa_id,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->groupBy('siswa_id')
            ->get();

        foreach ($attResults as $ar) {
            $attendanceMap[$ar->siswa_id] = [
                'sakit' => (int) $ar->s,
                'izin' => (int) $ar->i,
                'alfa' => (int) $ar->a,
            ];
        }

        // Finalization Status for this class
        $finalization = ReportFinalization::where('kelas', $selectedClass)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->first();

        return [
            'allYears' => $allYears,
            'selectedYear' => $selectedYear,
            'activeYear' => $activeYear,
            'tahunAjaranId' => $tahunAjaranId,
            'semester' => $semester,
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'subjects' => $subjects,
            'students' => $students,
            'stsScores' => $stsScores,
            'stsSummaries' => $stsSummaries,
            'attendanceMap' => $attendanceMap,
            'finalization' => $finalization,
        ];
    }

    public function index(Request $request)
    {
        $data = $this->buildRecapData($request);
        $data['title'] = 'Leger Raport';

        return view('pages.erapor.rekap-sts', $data);
    }

    /**
     * Print landscape PDF-ready view for Leger Raport kelas.
     */
    public function printPdf(Request $request, string $kelas)
    {
        $data = $this->buildRecapData($request, $kelas);
        $tahunAjaranId = $data['tahunAjaranId'];

        $schoolSettings = PengaturanSekolah::first();
        $raporSettings = RaporSetting::instance();
        $rombel = Rombel::with('jurusan')
            ->where('nama', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first()
            ?? Rombel::with('jurusan')->where('nama', $kelas)->orderByDesc('tahun_ajaran_id')->first();
        $jurusan = $rombel?->jurusan ?? Jurusan::resolveFromRombelName($kelas);
        $konsentrasiKeahlian = RaporArchiveService::resolveKonsentrasiKeahlian(
            $rombel,
            $jurusan,
            Rombel::inferMetaFromName($kelas)['kompetensi_keahlian'] ?? null
        );

        $homeroom = ClassHomeroom::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('guru')
            ->first();
        $waliKelasGuru = $homeroom?->guru ?? Jadwal::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('guru')
            ->first()?->guru;
        $kepalaSekolah = User::where('role', 'kepala_sekolah')->with('guru')->first();
        $tanggalCetak = $raporSettings->tanggal_rapor
            ? $raporSettings->tanggal_rapor->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        // Per-subject & overall summary stats (Rata-rata, Tertinggi, Terendah)
        $subjectStats = [];
        foreach ($data['subjects'] as $mapel) {
            $vals = [];
            foreach ($data['students'] as $siswa) {
                $rec = ($data['stsScores'][$siswa->id] ?? collect())->firstWhere('mata_pelajaran_id', $mapel->id);
                $v = $rec ? ($rec->final_score ?? $rec->asat_score ?? $rec->sts_score) : null;
                if ($v !== null) {
                    $vals[] = (float) $v;
                }
            }
            $subjectStats[$mapel->id] = [
                'avg' => !empty($vals) ? round(array_sum($vals) / count($vals), 1) : null,
                'max' => !empty($vals) ? round(max($vals), 1) : null,
                'min' => !empty($vals) ? round(min($vals), 1) : null,
            ];
        }

        $totalVals = [];
        $avgVals = [];
        foreach ($data['students'] as $siswa) {
            $sum = $data['stsSummaries'][$siswa->id] ?? null;
            if ($sum?->total_score !== null) {
                $totalVals[] = (float) $sum->total_score;
            }
            if ($sum?->average_score !== null) {
                $avgVals[] = (float) $sum->average_score;
            }
        }

        $overallStats = [
            'total_avg' => !empty($totalVals) ? round(array_sum($totalVals) / count($totalVals), 1) : null,
            'total_max' => !empty($totalVals) ? round(max($totalVals), 1) : null,
            'total_min' => !empty($totalVals) ? round(min($totalVals), 1) : null,
            'mean_avg' => !empty($avgVals) ? round(array_sum($avgVals) / count($avgVals), 1) : null,
            'mean_max' => !empty($avgVals) ? round(max($avgVals), 1) : null,
            'mean_min' => !empty($avgVals) ? round(min($avgVals), 1) : null,
        ];

        return view('pages.erapor.rekap-sts-print', array_merge($data, [
            'title' => 'Leger Raport ' . $kelas,
            'schoolSettings' => $schoolSettings,
            'raporSettings' => $raporSettings,
            'konsentrasiKeahlian' => $konsentrasiKeahlian,
            'homeroom' => $homeroom,
            'waliKelasGuru' => $waliKelasGuru,
            'kepalaSekolah' => $kepalaSekolah,
            'tanggalCetak' => $tanggalCetak,
            'subjectStats' => $subjectStats,
            'overallStats' => $overallStats,
        ]));
    }

    /**
     * Finalize class report
     */
    public function finalizeClass(Request $request, string $kelas)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranId = (int) $request->get('tahun_ajaran_id', $activeYear?->id);
        $semester = $request->get('semester', 'Ganjil');

        $record = ReportFinalization::updateOrCreate(
            [
                'kelas' => $kelas,
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
            ],
            [
                'status' => 'final',
                'reviewed_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'finalized_at' => now(),
                'notes' => $request->get('notes', 'Rapor telah ditinjau, difinalisasi, dan diarsipkan permanen.'),
            ]
        );

        $archivedCount = app(RaporArchiveService::class)->archiveClass(
            $kelas,
            $tahunAjaranId,
            $semester,
            ['sts', 'uas'],
            auth()->id()
        );

        EraporAuditLog::record(
            action: 'finalize',
            entityType: 'ReportFinalization',
            entityId: (string) $record->id,
            before: null,
            after: ['status' => 'final', 'archived_snapshots' => $archivedCount],
            reason: "Finalisasi & Arsip Rapor Kelas {$kelas} ({$semester})"
        );

        return back()->with('success', "Rapor kelas {$kelas} ({$semester}) berhasil difinalisasi & {$archivedCount} dokumen arsip digital (STS & UAS) dibekukan secara permanen.");
    }

    /**
     * Export Leger Raport to Excel
     */
    public function exportExcel(Request $request, string $kelas): StreamedResponse
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranId = (int) $request->get('tahun_ajaran_id', $activeYear?->id);
        $selectedYear = TahunAjaran::find($tahunAjaranId) ?? $activeYear;
        $semester = $request->get('semester', 'Ganjil');

        $spreadsheet = $this->exportService->generateClassSTSRecap($kelas, $tahunAjaranId, $semester);

        $safeClass = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kelas);
        $safeYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $selectedYear?->nama ?? '2026');
        $fileName = "Leger_Raport_{$safeClass}_{$safeYear}_{$semester}.xlsx";

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SchoolClass',
            entityId: $kelas,
            before: null,
            after: ['filename' => $fileName],
            reason: "Ekspor Leger Raport kelas {$kelas}"
        );

        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
