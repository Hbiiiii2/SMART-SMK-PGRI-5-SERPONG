<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\EraporAuditLog;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporArchive;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Services\EraporExportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BukuIndukController extends Controller
{
    public function __construct(
        protected EraporExportService $exportService
    ) {}

    /**
     * Display list of students and alumni with historical archive filters.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'semua');
        $kelasFilter = $request->get('kelas', 'semua');
        $tahunFilter = $request->get('tahun_ajaran_id', 'semua');
        $tahunLulusFilter = $request->get('tahun_lulus', 'semua');

        // High-level Statistics for School Archive
        $totalStudents = Siswa::count();
        $activeStudents = Siswa::where(function ($q) {
            $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
        })
            ->where(function ($q) {
                $q->whereNull('kelas')
                    ->orWhere(function ($q2) {
                        $q2->where('kelas', 'not like', '%alumni%')
                            ->where('kelas', 'not like', '%lulus%');
                    });
            })
            ->count();
        $alumniStudents = Siswa::where(function ($q) {
            $q->whereIn('status_siswa', ['lulus', 'mutasi_keluar', 'drop_out', 'mengundurkan_diri', 'wafat'])
                ->orWhere('kelas', 'like', '%alumni%')
                ->orWhere('kelas', 'like', '%lulus%');
        })->count();
        $totalGradesRecorded = SubjectGradeSummary::count();
        $totalArchivedRapors = RaporArchive::count();

        // Query students with relations & archive count
        $query = Siswa::query()
            ->with(['tahunAjaran', 'tahunAjaranLulus', 'rombel', 'riwayatKelas.tahunAjaran'])
            ->withCount('raporArchives');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nipd', 'like', "%{$search}%");
            });
        }

        if ($status === 'aktif') {
            $query->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })->where(function ($q) {
                $q->whereNull('kelas')
                    ->orWhere(function ($q2) {
                        $q2->where('kelas', 'not like', '%alumni%')
                            ->where('kelas', 'not like', '%lulus%');
                    });
            });
        } elseif ($status === 'alumni') {
            $query->where(function ($q) {
                $q->whereIn('status_siswa', ['lulus', 'mutasi_keluar', 'drop_out', 'mengundurkan_diri', 'wafat'])
                    ->orWhere('kelas', 'like', '%alumni%')
                    ->orWhere('kelas', 'like', '%lulus%');
            });
        }

        if ($kelasFilter !== 'semua' && !empty($kelasFilter)) {
            $query->where(function ($q) use ($kelasFilter) {
                $q->where('kelas', $kelasFilter)
                    ->orWhereHas('riwayatKelas', fn ($r) => $r->where('kelas', $kelasFilter));
            });
        }

        if ($tahunFilter !== 'semua' && !empty($tahunFilter)) {
            $query->where(function ($q) use ($tahunFilter) {
                $q->where('tahun_ajaran_id', $tahunFilter)
                    ->orWhere('tahun_ajaran_lulus_id', $tahunFilter)
                    ->orWhereHas('riwayatKelas', function ($r) use ($tahunFilter) {
                        $r->where('tahun_ajaran_id', $tahunFilter);
                    });
            });
        }

        if ($tahunLulusFilter !== 'semua' && is_numeric($tahunLulusFilter)) {
            $query->where('tahun_lulus', (int) $tahunLulusFilter);
        }

        $students = $query->orderBy('kelas')->orderBy('nama_lengkap')->paginate(20)->withQueryString();

        // Calculate cumulative averages for students in the current page
        $studentIds = $students->pluck('id');
        $summariesByStudent = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
            ->where(function ($q) {
                $q->whereNotNull('final_score')
                    ->orWhereNotNull('asat_score')
                    ->orWhereNotNull('sts_score');
            })
            ->get()
            ->groupBy('siswa_id');

        $studentStats = [];
        foreach ($students as $s) {
            $scores = ($summariesByStudent[$s->id] ?? collect())
                ->map(fn ($rec) => $rec->final_score ?? $rec->asat_score ?? $rec->sts_score)
                ->filter(fn ($v) => $v !== null);
            $avg = $scores->isNotEmpty() ? round($scores->avg(), 1) : null;
            $periodsCount = $s->riwayatKelas->count();
            $studentStats[$s->id] = [
                'cumulative_avg' => $avg,
                'periods_count' => $periodsCount,
                'grades_count' => $scores->count(),
            ];
        }

        $classes = \App\Models\Rombel::classNames(null, true, false);
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $graduationYears = Siswa::whereNotNull('tahun_lulus')
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus');
        $schoolSettings = PengaturanSekolah::first();

        return view('pages.erapor.buku-induk', [
            'title' => 'Buku Induk & Arsip Siswa / Alumni',
            'students' => $students,
            'studentStats' => $studentStats,
            'classes' => $classes,
            'allYears' => $allYears,
            'graduationYears' => $graduationYears,
            'schoolSettings' => $schoolSettings,
            'search' => $search,
            'status' => $status,
            'kelasFilter' => $kelasFilter,
            'tahunFilter' => $tahunFilter,
            'tahunLulusFilter' => $tahunLulusFilter,
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'alumniStudents' => $alumniStudents,
            'totalGradesRecorded' => $totalGradesRecorded,
            'totalArchivedRapors' => $totalArchivedRapors,
        ]);
    }

    /**
     * Show comprehensive individual student academic transcript across all years & semesters.
     */
    public function show(Siswa $siswa)
    {
        $siswa->load(['tahunAjaran', 'tahunAjaranLulus', 'rombel.waliKelas', 'profil', 'orangTuas', 'latestPeriodik', 'riwayatKelas.tahunAjaran']);

        // Academic periods recorded in history (preferring real class entries over generic Alumni/Lulus placeholder rows)
        $rawPeriods = RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->with('tahunAjaran')
            ->get()
            ->sortBy(function ($p) {
                $start = $p->tahunAjaran?->tanggal_mulai
                    ? \Carbon\Carbon::parse($p->tahunAjaran->tanggal_mulai)->format('Y-m-d')
                    : ($p->tahunAjaran?->nama ?? '9999');
                $semOrd = strtolower((string) $p->semester) === 'ganjil' ? '1' : '2';
                return "{$start}_{$semOrd}";
            })
            ->values();

        $realPeriods = $rawPeriods->filter(function ($p) {
            $k = strtolower(trim((string) $p->kelas));
            return $k !== '' && !str_starts_with($k, 'alumni') && !str_starts_with($k, 'lulus');
        })->values();

        $periods = $realPeriods->isNotEmpty() ? $realPeriods : $rawPeriods;

        if ($periods->isEmpty()) {
            $activeYear = TahunAjaran::where('is_aktif', true)->first();
            $periods = collect([(object) [
                'tahun_ajaran_id' => $activeYear?->id ?? 1,
                'semester' => 'Ganjil',
                'kelas' => $siswa->kelas,
                'status' => $siswa->is_alumni ? 'alumni' : 'aktif',
                'tahunAjaran' => $activeYear,
            ]]);
        }

        // Load archived immutable snapshots keyed by "{tahun_ajaran_id}_{semester}_{jenis_rapor}"
        $archives = RaporArchive::where('siswa_id', $siswa->id)
            ->get()
            ->keyBy(fn ($a) => "{$a->tahun_ajaran_id}_{$a->semester}_{$a->jenis_rapor}");

        // Subjects relevant to student's historical classes & grades
        $subjects = MataPelajaran::forStudentHistory($siswa, $periods);

        // All grade summaries for this student
        $allSummaries = SubjectGradeSummary::where('siswa_id', $siswa->id)->get();
        $stsSummaries = StudentStsSummary::where('siswa_id', $siswa->id)
            ->get()
            ->keyBy(fn($item) => $item->tahun_ajaran_id . '_' . $item->semester);

        // Attendance stats per period
        $attResults = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        $schoolSettings = PengaturanSekolah::first();

        // Calculate overall cumulative score
        $allScores = $allSummaries
            ->map(fn ($rec) => $rec->final_score ?? $rec->asat_score ?? $rec->sts_score)
            ->filter(fn ($v) => $v !== null);
        $cumulativeAvg = $allScores->isNotEmpty() ? round($allScores->avg(), 1) : null;

        return view('pages.erapor.buku-induk-detail', [
            'title' => "Buku Induk & Transkrip - {$siswa->nama_lengkap}",
            'siswa' => $siswa,
            'periods' => $periods,
            'archives' => $archives,
            'subjects' => $subjects,
            'allSummaries' => $allSummaries,
            'stsSummaries' => $stsSummaries,
            'attendance' => $attResults,
            'schoolSettings' => $schoolSettings,
            'cumulativeAvg' => $cumulativeAvg,
        ]);
    }

    /**
     * Printable A4 view of the student transcript / Buku Induk
     */
    public function print(Siswa $siswa)
    {
        $siswa->load(['tahunAjaran', 'rombel.waliKelas', 'profil', 'orangTuas', 'latestPeriodik', 'riwayatKelas.tahunAjaran']);

        $periods = RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->with('tahunAjaran')
            ->get()
            ->filter(function ($p) {
                $k = strtolower(trim((string) $p->kelas));
                return $k !== '' && !str_starts_with($k, 'alumni') && !str_starts_with($k, 'lulus');
            })
            ->sortBy(function ($p) {
                $start = $p->tahunAjaran?->tanggal_mulai
                    ? \Carbon\Carbon::parse($p->tahunAjaran->tanggal_mulai)->format('Y-m-d')
                    : ($p->tahunAjaran?->nama ?? '9999');
                $semOrd = strtolower((string) $p->semester) === 'ganjil' ? '1' : '2';
                return "{$start}_{$semOrd}";
            })
            ->values();

        if ($periods->isEmpty()) {
            $activeYear = TahunAjaran::where('is_aktif', true)->first();
            $periods = collect([(object) [
                'tahun_ajaran_id' => $activeYear?->id ?? 1,
                'semester' => 'Ganjil',
                'kelas' => $siswa->kelas,
                'status' => $siswa->is_alumni ? 'alumni' : 'aktif',
                'tahunAjaran' => $activeYear,
            ]]);
        }

        $subjects = MataPelajaran::forStudentHistory($siswa, $periods);
        $allSummaries = SubjectGradeSummary::where('siswa_id', $siswa->id)->get();
        $stsSummaries = StudentStsSummary::where('siswa_id', $siswa->id)
            ->get()
            ->keyBy(fn($item) => $item->tahun_ajaran_id . '_' . $item->semester);

        $attResults = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        $schoolSettings = PengaturanSekolah::first();
        $allScores = $allSummaries
            ->map(fn ($rec) => $rec->final_score ?? $rec->asat_score ?? $rec->sts_score)
            ->filter(fn ($v) => $v !== null);
        $cumulativeAvg = $allScores->isNotEmpty() ? round($allScores->avg(), 1) : null;

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SiswaTranscript',
            entityId: (string) $siswa->id,
            before: null,
            after: ['action' => 'print'],
            reason: "Cetak Buku Induk / Transkrip Siswa {$siswa->nama_lengkap}"
        );

        return view('pages.erapor.buku-induk-print', [
            'title' => "Cetak Transkrip - {$siswa->nama_lengkap}",
            'siswa' => $siswa,
            'periods' => $periods,
            'subjects' => $subjects,
            'allSummaries' => $allSummaries,
            'stsSummaries' => $stsSummaries,
            'attendance' => $attResults,
            'schoolSettings' => $schoolSettings,
            'cumulativeAvg' => $cumulativeAvg,
        ]);
    }

    /**
     * Export student multi-semester transcript to Excel
     */
    public function exportExcel(Siswa $siswa): StreamedResponse
    {
        $spreadsheet = $this->exportService->generateStudentTranscript($siswa);

        $safeNisn = preg_replace('/[^0-9]/', '', $siswa->nisn);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $siswa->nama_lengkap);
        $fileName = "Transkrip_Buku_Induk_{$safeNisn}_{$safeName}.xlsx";

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SiswaTranscript',
            entityId: (string) $siswa->id,
            before: null,
            after: ['filename' => $fileName],
            reason: "Ekspor Transkrip Excel Buku Induk Siswa {$siswa->nama_lengkap}"
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
