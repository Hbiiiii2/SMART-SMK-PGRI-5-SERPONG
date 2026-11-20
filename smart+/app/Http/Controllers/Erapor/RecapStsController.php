<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\EraporAuditLog;
use App\Models\MataPelajaran;
use App\Models\ReportFinalization;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Services\EraporExportService;
use App\Services\GradingCalculationService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecapStsController extends Controller
{
    public function __construct(
        protected GradingCalculationService $gradingService,
        protected EraporExportService $exportService
    ) {}

    public function index(Request $request)
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
            $classes = $historicalClasses->filter(fn($c) => !str_starts_with(strtolower($c), 'alumni') && !str_starts_with(strtolower($c), 'lulus'))->values();
        } else {
            $classes = Siswa::select('kelas')->distinct()->pluck('kelas')
                ->filter(fn($c) => !str_starts_with(strtolower($c), 'alumni') && !str_starts_with(strtolower($c), 'lulus'))->values();
        }

        $selectedClass = $request->get('kelas', $classes->first());

        // Ensure calculations are up to date
        if ($selectedClass) {
            $this->gradingService->recalculateClassSTS($selectedClass, $tahunAjaranId, $semester);
        }

        $subjects = MataPelajaran::orderBy('nama')->get();

        // Historical vs current student resolution
        $historicalStudentIds = \App\Models\RiwayatKelasSiswa::where('kelas', $selectedClass)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->pluck('siswa_id');

        if ($historicalStudentIds->isNotEmpty()) {
            $students = Siswa::whereIn('id', $historicalStudentIds)->orderBy('nama_lengkap')->get();
        } else {
            $students = Siswa::where('kelas', $selectedClass)->orderBy('nama_lengkap')->get();
        }

        $studentIds = $students->pluck('id');

        // Fetch STS scores per mapel
        $stsScores = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        // Fetch STS Summaries (Total, Avg, Rank)
        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $studentIds)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        // Fetch Attendance counts from SMART+ (Sakit, Izin, Alfa) per distinct date
        $attendanceMap = [];
        $attQuery = AbsensiSiswa::whereIn('siswa_id', $studentIds);
        if ($selectedYear?->tanggal_mulai && $selectedYear?->tanggal_selesai) {
            $attQuery->whereBetween('tanggal', [
                \Carbon\Carbon::parse($selectedYear->tanggal_mulai)->toDateString(),
                \Carbon\Carbon::parse($selectedYear->tanggal_selesai)->toDateString(),
            ]);
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

        return view('pages.erapor.rekap-sts', [
            'title' => 'Rekapitulasi Nilai STS & Peringkat Kelas',
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
        ]);
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
                'notes' => $request->get('notes', 'Rapor STS telah ditinjau dan difinalisasi.'),
            ]
        );

        EraporAuditLog::record(
            action: 'finalize',
            entityType: 'ReportFinalization',
            entityId: (string) $record->id,
            before: null,
            after: ['status' => 'final'],
            reason: "Finalisasi Rapor STS Kelas {$kelas}"
        );

        return back()->with('success', "Rapor STS kelas {$kelas} berhasil difinalisasi.");
    }

    /**
     * Export Class STS Recap to Excel
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
        $fileName = "Rekap_STS_{$safeClass}_{$safeYear}_{$semester}.xlsx";

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SchoolClass',
            entityId: $kelas,
            before: null,
            after: ['filename' => $fileName],
            reason: "Ekspor rekapitulasi STS kelas {$kelas}"
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
