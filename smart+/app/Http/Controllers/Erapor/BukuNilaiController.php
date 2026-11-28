<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\EraporAuditLog;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Services\CbtIntegrationService;
use App\Services\GradingCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BukuNilaiController extends Controller
{
    public function __construct(
        protected GradingCalculationService $gradingService,
        protected CbtIntegrationService $cbtService
    ) {}

    /**
     * Roles with school-wide access to all classes and subjects.
     */
    protected function isPrivilegedRole(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin_sekolah', 'kepala_sekolah'], true);
    }

    /**
     * Resolve schedules belonging to the authenticated teacher.
     */
    protected function resolveTeacherSchedules(?int $tahunId): Collection
    {
        $guru = auth()->user()?->guru;
        if (!$guru) {
            return collect();
        }

        $base = Jadwal::where('guru_id', $guru->id)
            ->whereNotNull('mata_pelajaran_id')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '');

        if ($tahunId) {
            $scoped = (clone $base)->where('tahun_ajaran_id', $tahunId)->get();
            if ($scoped->isNotEmpty()) {
                return $scoped;
            }

            if (Jadwal::where('tahun_ajaran_id', $tahunId)->doesntExist()) {
                return (clone $base)->get();
            }

            return collect();
        }

        return $base->get();
    }

    /**
     * Enforce that non-privileged teachers can only access/mutate classes & subjects on their Jadwal.
     */
    protected function assertTeacherCanAccess(?int $tahunId, ?string $kelas, int $mapelId): void
    {
        if ($this->isPrivilegedRole()) {
            return;
        }

        $schedules = $this->resolveTeacherSchedules($tahunId);
        if ($kelas !== null && $kelas !== '') {
            $allowed = $schedules->contains(
                fn ($j) => $j->kelas === $kelas && (int) $j->mata_pelajaran_id === $mapelId
            );
        } else {
            $allowed = $schedules->contains(
                fn ($j) => (int) $j->mata_pelajaran_id === $mapelId
            );
        }

        if (!$allowed) {
            abort(403, 'Anda tidak memiliki jadwal mengajar pada kelas dan mata pelajaran tersebut.');
        }
    }

    /**
     * Ensure standard NH + ASAT assessment components exist and are active.
     */
    protected function ensureStandardComponents()
    {
        $standardComponents = [
            ['code' => 'tugas', 'name' => 'Tugas', 'default_count' => 1, 'default_weight' => 40.0, 'order' => 1],
            ['code' => 'sh', 'name' => 'Sumatif Harian / Projek / UH', 'default_count' => 1, 'default_weight' => 60.0, 'order' => 2],
            ['code' => 'asat', 'name' => 'Nilai Murni ASAT (UAS)', 'default_count' => 1, 'default_weight' => 100.0, 'order' => 3],
        ];

        foreach ($standardComponents as $sc) {
            AssessmentComponent::updateOrCreate(
                ['code' => $sc['code']],
                array_merge($sc, ['is_active' => true])
            );
        }

        return AssessmentComponent::whereIn('code', ['tugas', 'sh', 'asat'])
            ->orderBy('order')
            ->get()
            ->keyBy('code');
    }

    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::latest()->first();
        $tahunAjaranId = $request->get('tahun_ajaran_id', $activeYear?->id);
        $tahunId = $tahunAjaranId ? (int) $tahunAjaranId : null;
        $semester = $request->get('semester', 'Ganjil');
        $isGuru = !$this->isPrivilegedRole();

        if ($isGuru) {
            $schedules = $this->resolveTeacherSchedules($tahunId);
            $allowedByClass = $schedules->groupBy('kelas')->map(
                fn ($rows) => $rows->pluck('mata_pelajaran_id')->map(fn ($id) => (int) $id)->unique()->values()
            );
            $classes = $allowedByClass->keys()->sort()->values();
            $taughtClasses = $classes;

            if ($classes->isEmpty()) {
                if ($request->filled('kelas') || $request->filled('mata_pelajaran_id')) {
                    abort(403, 'Anda belum memiliki jadwal mengajar pada kelas atau mata pelajaran tersebut.');
                }

                $selectedClass = '';
                $subjects = collect();
                $taughtSubjectIds = collect();
                $selectedSubjectId = 0;
                $selectedSubject = null;
            } else {
                if ($request->filled('kelas')) {
                    $selectedClass = (string) $request->query('kelas');
                    if (!$classes->contains($selectedClass)) {
                        abort(403, 'Anda tidak memiliki jadwal mengajar pada kelas tersebut.');
                    }
                } else {
                    $selectedClass = (string) $classes->first();
                }

                $allowedMapelIds = $allowedByClass->get($selectedClass, collect());
                $taughtSubjectIds = $allowedMapelIds;
                $subjects = MataPelajaran::sortSubjects(MataPelajaran::whereIn('id', $allowedMapelIds)->get());

                if ($request->filled('mata_pelajaran_id')) {
                    $requestedSubjectId = (int) $request->query('mata_pelajaran_id');
                    if (!$allowedMapelIds->contains($requestedSubjectId)) {
                        abort(403, 'Anda tidak memiliki jadwal mengajar untuk mata pelajaran tersebut pada kelas ' . $selectedClass . '.');
                    }
                    $selectedSubjectId = $requestedSubjectId;
                } else {
                    $selectedSubjectId = (int) ($subjects->first()?->id ?? 0);
                }

                $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId);
            }
        } else {
            $taughtClasses = collect();
            $taughtSubjectIds = collect();
            $classes = Rombel::classNames($tahunId);
            $selectedClass = (string) $request->get('kelas', $classes->first() ?? '');
            $subjects = $selectedClass
                ? MataPelajaran::forClass($selectedClass, $tahunId, $semester)
                : MataPelajaran::sortSubjects(MataPelajaran::all());

            $requestedSubjectId = $request->filled('mata_pelajaran_id') ? (int) $request->get('mata_pelajaran_id') : null;
            $selectedSubject = ($requestedSubjectId ? $subjects->firstWhere('id', $requestedSubjectId) : null) ?? $subjects->first();
            $selectedSubjectId = (int) ($selectedSubject?->id ?? 0);
        }

        $standardMap = $this->ensureStandardComponents();
        $components = $standardMap->values();

        if ($selectedSubject && $selectedClass !== '' && $tahunAjaranId) {
            $existingItems = AssessmentItem::with('component')
                ->where('mata_pelajaran_id', $selectedSubjectId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->get();

            $hasDailyItems = $existingItems->contains(fn ($it) => $it->resolveWeightGroup() !== 'asat');
            $hasAsatItems = $existingItems->contains(fn ($it) => $it->resolveWeightGroup() === 'asat');

            // Default provision: NH 1 (Tugas 1 + Sumatif Harian 1)
            if (!$hasDailyItems) {
                AssessmentItem::create([
                    'assessment_component_id' => $standardMap['tugas']->id,
                    'daily_block' => 1,
                    'weight_group' => 'tugas',
                    'mata_pelajaran_id' => $selectedSubjectId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                    'sequence' => 1,
                    'title' => 'Tugas 1',
                    'max_score' => 100.0,
                ]);
                AssessmentItem::create([
                    'assessment_component_id' => $standardMap['sh']->id,
                    'daily_block' => 1,
                    'weight_group' => 'sumatif',
                    'mata_pelajaran_id' => $selectedSubjectId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                    'sequence' => 1,
                    'title' => 'Sumatif Harian 1',
                    'max_score' => 100.0,
                ]);
            }

            // Default provision: Nilai Murni ASAT
            if (!$hasAsatItems) {
                AssessmentItem::create([
                    'assessment_component_id' => $standardMap['asat']->id,
                    'daily_block' => null,
                    'weight_group' => 'asat',
                    'mata_pelajaran_id' => $selectedSubjectId,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                    'sequence' => 1,
                    'title' => 'Nilai Murni ASAT',
                    'max_score' => 100.0,
                ]);
            }

            $items = AssessmentItem::with('component')
                ->where('mata_pelajaran_id', $selectedSubjectId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->orderByRaw('CASE WHEN daily_block IS NULL THEN 999 ELSE daily_block END ASC')
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            // Build structured dailyBlocks and asatItems for matrix display
            $dailyBlocks = [];
            $asatItems = collect();

            foreach ($items as $it) {
                $group = $it->resolveWeightGroup();
                if ($group === 'asat') {
                    $asatItems->push($it);
                    continue;
                }
                $blockNum = $it->resolveDailyBlock() ?? 1;
                if (!isset($dailyBlocks[$blockNum])) {
                    $dailyBlocks[$blockNum] = [
                        'tugas' => collect(),
                        'sumatif' => collect(),
                        'items' => collect(),
                    ];
                }
                if ($group === 'tugas') {
                    $dailyBlocks[$blockNum]['tugas']->push($it);
                } else {
                    $dailyBlocks[$blockNum]['sumatif']->push($it);
                }
            }

            ksort($dailyBlocks);
            foreach ($dailyBlocks as $bNum => $groups) {
                $dailyBlocks[$bNum]['items'] = $groups['tugas']->concat($groups['sumatif'])->values();
            }
            $nextDailyBlock = !empty($dailyBlocks) ? (max(array_keys($dailyBlocks)) + 1) : 2;

            // Fetch students
            $students = Siswa::where('kelas', $selectedClass)->orderBy('nama_lengkap')->get();

            // Fetch student scores
            $studentIds = $students->pluck('id');
            $itemIds = $items->pluck('id');

            $scores = StudentAssessmentScore::whereIn('siswa_id', $studentIds)
                ->whereIn('assessment_item_id', $itemIds)
                ->get()
                ->groupBy('siswa_id');

            // Fetch grade summaries
            $summaries = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
                ->where('mata_pelajaran_id', $selectedSubjectId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->get()
                ->keyBy('siswa_id');

            // Available LMS assignments for this subject & class
            $lmsTugasQuery = Tugas::where('mata_pelajaran_id', $selectedSubjectId)
                ->where('kelas', $selectedClass);
            if ($isGuru && auth()->user()?->guru) {
                $lmsTugasQuery->where('guru_id', auth()->user()->guru->id);
            }
            $lmsTugasList = $lmsTugasQuery->get();

            $cbtExams = $this->cbtService->fetchAvailableExams($selectedSubjectId, $selectedClass);
        } else {
            $items = collect();
            $dailyBlocks = [];
            $asatItems = collect();
            $nextDailyBlock = 2;
            $students = collect();
            $scores = collect();
            $summaries = collect();
            $lmsTugasList = collect();
            $cbtExams = [];
        }

        return view('pages.erapor.buku-nilai', [
            'title' => 'Buku Nilai Mata Pelajaran',
            'activeYear' => $activeYear,
            'tahunAjaranId' => $tahunAjaranId,
            'semester' => $semester,
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'subjects' => $subjects,
            'selectedSubject' => $selectedSubject,
            'selectedSubjectId' => $selectedSubjectId,
            'components' => $components,
            'items' => $items,
            'dailyBlocks' => $dailyBlocks,
            'asatItems' => $asatItems,
            'nextDailyBlock' => $nextDailyBlock,
            'students' => $students,
            'scores' => $scores,
            'summaries' => $summaries,
            'lmsTugasList' => $lmsTugasList,
            'taughtClasses' => $taughtClasses,
            'taughtSubjectIds' => $taughtSubjectIds,
            'cbtExams' => $cbtExams,
            'isGuru' => $isGuru,
        ]);
    }

    /**
     * Add an optional Nilai Harian block (NH 2, NH 3, ...) with default Tugas 1 & Sumatif Harian 1.
     */
    public function storeDailyBlock(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $this->assertTeacherCanAccess(
            (int) $request->tahun_ajaran_id,
            $request->input('kelas'),
            (int) $request->mata_pelajaran_id
        );

        $standardMap = $this->ensureStandardComponents();

        $maxBlock = AssessmentItem::where('mata_pelajaran_id', $request->mata_pelajaran_id)
            ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
            ->where('semester', $request->semester)
            ->whereNotNull('daily_block')
            ->max('daily_block') ?? 1;

        $nextBlock = max(2, (int) $maxBlock + 1);

        AssessmentItem::create([
            'assessment_component_id' => $standardMap['tugas']->id,
            'daily_block' => $nextBlock,
            'weight_group' => 'tugas',
            'mata_pelajaran_id' => $request->mata_pelajaran_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'semester' => $request->semester,
            'sequence' => 1,
            'title' => 'Tugas 1',
            'max_score' => 100.0,
        ]);

        AssessmentItem::create([
            'assessment_component_id' => $standardMap['sh']->id,
            'daily_block' => $nextBlock,
            'weight_group' => 'sumatif',
            'mata_pelajaran_id' => $request->mata_pelajaran_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'semester' => $request->semester,
            'sequence' => 1,
            'title' => 'Sumatif Harian 1',
            'max_score' => 100.0,
        ]);

        return back()->with('success', "Blok Nilai Harian {$nextBlock} berhasil ditambahkan.");
    }

    /**
     * Delete an optional Nilai Harian block (block > 1) and recalculate affected student summaries.
     */
    public function destroyDailyBlock(Request $request, int $block)
    {
        if ($block <= 1) {
            return back()->with('error', 'Blok Nilai Harian 1 adalah blok utama dan tidak dapat dihapus.');
        }

        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $mapelId = (int) $request->mata_pelajaran_id;
        $tahunId = (int) $request->tahun_ajaran_id;
        $semester = $request->semester;

        $this->assertTeacherCanAccess($tahunId, $request->input('kelas'), $mapelId);

        $items = AssessmentItem::where('mata_pelajaran_id', $mapelId)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->where('daily_block', $block)
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', "Blok Nilai Harian {$block} tidak ditemukan.");
        }

        $itemIds = $items->pluck('id');
        $affectedStudentIds = StudentAssessmentScore::whereIn('assessment_item_id', $itemIds)
            ->distinct()
            ->pluck('siswa_id');

        StudentAssessmentScore::whereIn('assessment_item_id', $itemIds)->delete();
        AssessmentItem::whereIn('id', $itemIds)->delete();

        $affectedClasses = [];
        foreach ($affectedStudentIds as $siswaId) {
            $this->gradingService->recalculateSubjectGrade((int) $siswaId, $mapelId, $tahunId, $semester);
            $siswa = Siswa::find($siswaId);
            if ($siswa && $siswa->kelas) {
                $affectedClasses[$siswa->kelas] = true;
            }
        }

        foreach (array_keys($affectedClasses) as $cls) {
            $this->gradingService->recalculateClassSTS($cls, $tahunId, $semester);
        }

        return back()->with('success', "Blok Nilai Harian {$block} berhasil dihapus.");
    }

    /**
     * Store dynamic new assessment item (e.g. Tugas 2, Projek 1, Ujian Harian 1, ASAT Susulan)
     */
    public function storeItem(Request $request)
    {
        $request->validate([
            'assessment_component_id' => 'nullable|exists:assessment_components,id',
            'weight_group' => 'nullable|in:tugas,sumatif,asat',
            'daily_block' => 'nullable|integer|min:1|max:20',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'title' => 'required|string|max:150',
            'max_score' => 'nullable|numeric|min:10|max:100',
        ]);

        $this->assertTeacherCanAccess(
            (int) $request->tahun_ajaran_id,
            $request->input('kelas'),
            (int) $request->mata_pelajaran_id
        );

        if (!$request->filled('assessment_component_id') && !$request->filled('weight_group')) {
            return back()->withErrors(['weight_group' => 'Pilih kategori komponen atau kelompok bobot penilaian.']);
        }

        $standardMap = $this->ensureStandardComponents();

        $weightGroup = $request->input('weight_group');
        $componentId = $request->input('assessment_component_id');

        if (empty($weightGroup) && $componentId) {
            $comp = AssessmentComponent::find($componentId);
            $code = strtolower((string) ($comp?->code ?? ''));
            $weightGroup = match (true) {
                $code === 'tugas' => 'tugas',
                in_array($code, ['asat', 'sts'], true) => 'asat',
                default => 'sumatif',
            };
        }

        if (empty($componentId)) {
            $componentId = match ($weightGroup) {
                'tugas' => $standardMap['tugas']->id,
                'asat' => $standardMap['asat']->id,
                default => $standardMap['sh']->id,
            };
        }

        $dailyBlock = ($weightGroup === 'asat')
            ? null
            : ((int) $request->input('daily_block', 1));

        $maxSeqQuery = AssessmentItem::where('mata_pelajaran_id', $request->mata_pelajaran_id)
            ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
            ->where('semester', $request->semester)
            ->where('weight_group', $weightGroup);

        if ($dailyBlock === null) {
            $maxSeqQuery->whereNull('daily_block');
        } else {
            $maxSeqQuery->where('daily_block', $dailyBlock);
        }

        $maxSeq = $maxSeqQuery->max('sequence') ?? 0;

        $item = AssessmentItem::create([
            'assessment_component_id' => $componentId,
            'daily_block' => $dailyBlock,
            'weight_group' => $weightGroup,
            'mata_pelajaran_id' => $request->mata_pelajaran_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'semester' => $request->semester,
            'sequence' => $maxSeq + 1,
            'title' => $request->title,
            'max_score' => $request->max_score ?? 100.0,
        ]);

        return back()->with('success', "Kolom penilaian '{$item->title}' berhasil ditambahkan ke Buku Nilai.");
    }

    /**
     * Delete assessment item and recalculate affected student summaries
     */
    public function destroyItem(int $id)
    {
        $item = AssessmentItem::with('component')->findOrFail($id);

        $this->assertTeacherCanAccess((int) $item->tahun_ajaran_id, null, (int) $item->mata_pelajaran_id);

        if ($item->resolveWeightGroup() === 'asat') {
            $asatCount = AssessmentItem::with('component')
                ->where('mata_pelajaran_id', $item->mata_pelajaran_id)
                ->where('tahun_ajaran_id', $item->tahun_ajaran_id)
                ->where('semester', $item->semester)
                ->get()
                ->filter(fn ($it) => $it->resolveWeightGroup() === 'asat')
                ->count();

            if ($asatCount <= 1) {
                return back()->with('error', 'Kolom Nilai Murni ASAT utama tidak dapat dihapus.');
            }
        }

        $title = $item->title;
        $mapelId = (int) $item->mata_pelajaran_id;
        $tahunId = (int) $item->tahun_ajaran_id;
        $semester = $item->semester;

        $affectedStudentIds = StudentAssessmentScore::where('assessment_item_id', $id)
            ->distinct()
            ->pluck('siswa_id');

        // Delete associated scores
        StudentAssessmentScore::where('assessment_item_id', $id)->delete();
        $item->delete();

        $affectedClasses = [];
        foreach ($affectedStudentIds as $siswaId) {
            $this->gradingService->recalculateSubjectGrade((int) $siswaId, $mapelId, $tahunId, $semester);
            $siswa = Siswa::find($siswaId);
            if ($siswa && $siswa->kelas) {
                $affectedClasses[$siswa->kelas] = true;
            }
        }

        foreach (array_keys($affectedClasses) as $cls) {
            $this->gradingService->recalculateClassSTS($cls, $tahunId, $semester);
        }

        return back()->with('success', "Kolom penilaian '{$title}' berhasil dihapus.");
    }

    /**
     * Save score update
     */
    public function updateScore(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'assessment_item_id' => 'required|exists:assessment_items,id',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        $item = AssessmentItem::findOrFail($request->assessment_item_id);
        $siswa = Siswa::findOrFail($request->siswa_id);

        $this->assertTeacherCanAccess(
            (int) $item->tahun_ajaran_id,
            $siswa->kelas,
            (int) $item->mata_pelajaran_id
        );

        $score = $request->score !== null ? (float) $request->score : null;

        $existing = StudentAssessmentScore::where('siswa_id', $request->siswa_id)
            ->where('assessment_item_id', $request->assessment_item_id)
            ->first();

        $beforeVal = $existing?->score;

        $record = StudentAssessmentScore::updateOrCreate(
            [
                'siswa_id' => $request->siswa_id,
                'assessment_item_id' => $request->assessment_item_id,
            ],
            [
                'score' => $score,
                'source_type' => 'manual',
                'updated_by' => auth()->id(),
            ]
        );

        // Recalculate summary
        $summary = $this->gradingService->recalculateSubjectGrade(
            $request->siswa_id,
            $item->mata_pelajaran_id,
            $item->tahun_ajaran_id,
            $item->semester
        );

        // Recalculate class ranking & STS summary
        if ($siswa->kelas) {
            $this->gradingService->recalculateClassSTS(
                $siswa->kelas,
                (int) $item->tahun_ajaran_id,
                $item->semester
            );
        }

        EraporAuditLog::record(
            action: 'update_score',
            entityType: 'StudentAssessmentScore',
            entityId: (string) $record->id,
            before: ['score' => $beforeVal],
            after: ['score' => $score],
            reason: 'Pembaruan nilai manual oleh guru/admin'
        );

        return response()->json([
            'success' => true,
            'message' => 'Nilai berhasil disimpan.',
            'summary' => $summary,
        ]);
    }

    /**
     * Pull grades directly from LMS task
     */
    public function syncFromLms(Request $request)
    {
        $request->validate([
            'tugas_id' => 'required|exists:tugas,id',
            'sequence' => 'nullable|integer|min:1|max:20',
            'daily_block' => 'nullable|integer|min:1|max:20',
            'target_assessment_item_id' => 'nullable|exists:assessment_items,id',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $tugas = Tugas::findOrFail($request->tugas_id);
        $this->assertTeacherCanAccess(
            (int) $request->tahun_ajaran_id,
            $request->input('kelas') ?: $tugas->kelas,
            (int) $request->mata_pelajaran_id
        );

        $result = $this->cbtService->syncLmsTaskScores(
            tugasId: (int) $request->tugas_id,
            mataPelajaranId: (int) $request->mata_pelajaran_id,
            tahunAjaranId: (int) $request->tahun_ajaran_id,
            semester: $request->semester,
            sequence: (int) $request->input('sequence', 1),
            dailyBlock: (int) $request->input('daily_block', 1),
            targetAssessmentItemId: $request->target_assessment_item_id ? (int) $request->target_assessment_item_id : null
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Pull grades directly from CBT API with flexible mapping and remedial strategy
     */
    public function syncFromCbt(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'cbt_exam_id' => 'nullable|integer',
            'target_assessment_item_id' => 'nullable|integer',
            'sync_mode' => 'nullable|string|in:replace,highest,fill_empty',
            'component_code' => 'nullable|string',
        ]);

        $this->assertTeacherCanAccess(
            (int) $request->tahun_ajaran_id,
            $request->get('kelas'),
            (int) $request->mata_pelajaran_id
        );

        $result = $this->cbtService->syncCbtViaApi(
            cbtExamId: $request->cbt_exam_id ? (int) $request->cbt_exam_id : null,
            mataPelajaranId: $request->mata_pelajaran_id,
            tahunAjaranId: $request->tahun_ajaran_id,
            semester: $request->semester,
            kelas: $request->get('kelas'),
            targetAssessmentItemId: $request->target_assessment_item_id ? (int) $request->target_assessment_item_id : null,
            componentCode: $request->get('component_code', 'asat'),
            syncMode: $request->get('sync_mode', 'highest')
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
