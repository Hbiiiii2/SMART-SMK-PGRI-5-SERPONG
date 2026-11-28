<?php

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;

class GradingCalculationService
{
    /**
     * Calculate component average according to PRD 7.2:
     * - Average = sum of valid filled scores / count of valid filled scores.
     * - Null / empty scores are ignored and NOT treated as 0.
     * - Score of 0 is valid.
     * - Returns null if no valid scores are present (status "Belum ada nilai").
     */
    public function calculateComponentAverage(array $scores, int $decimals = 1): ?float
    {
        $validScores = [];

        foreach ($scores as $score) {
            if ($score !== null && $score !== '' && is_numeric($score)) {
                $val = (float) $score;
                if ($val >= 0 && $val <= 100) {
                    $validScores[] = $val;
                }
            }
        }

        if (empty($validScores)) {
            return null; // "Belum ada nilai"
        }

        $avg = array_sum($validScores) / count($validScores);
        return round($avg, $decimals);
    }

    /**
     * Recalculate a student's subject grade summary for a given period using
     * dynamic Nilai Harian (NH 1..n: 40% Tugas + 60% Sumatif/Projek/UH),
     * Rata-rata SH, Nilai Murni ASAT, and Nilai Akhir = AVERAGE(NH 1..n, ASAT).
     */
    public function recalculateSubjectGrade(
        int $siswaId,
        int $mataPelajaranId,
        int $tahunAjaranId,
        string $semester = 'Ganjil'
    ): SubjectGradeSummary {
        $items = AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $mataPelajaranId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->orderBy('daily_block')
            ->orderBy('assessment_component_id')
            ->orderBy('sequence')
            ->get();

        $itemIds = $items->pluck('id');
        $scoresByItemId = StudentAssessmentScore::where('siswa_id', $siswaId)
            ->whereIn('assessment_item_id', $itemIds)
            ->pluck('score', 'assessment_item_id');

        $blockScores = [];
        $allTugasScores = [];
        $allSumatifScores = [];
        $allAsatScores = [];

        foreach ($items as $item) {
            $group = $item->resolveWeightGroup();
            $score = $scoresByItemId[$item->id] ?? null;

            if ($group === 'asat') {
                $allAsatScores[] = $score;
                continue;
            }

            $block = $item->resolveDailyBlock() ?? 1;
            if (!isset($blockScores[$block])) {
                $blockScores[$block] = ['tugas' => [], 'sumatif' => []];
            }

            if ($group === 'tugas') {
                $blockScores[$block]['tugas'][] = $score;
                $allTugasScores[] = $score;
            } else {
                $blockScores[$block]['sumatif'][] = $score;
                $allSumatifScores[] = $score;
            }
        }

        ksort($blockScores);

        $dailyScores = [];
        $finalParts = [];

        foreach ($blockScores as $blockNum => $groups) {
            $blockTugasAvg = $this->calculateComponentAverage($groups['tugas'], 1);
            $blockSumatifAvg = $this->calculateComponentAverage($groups['sumatif'], 1);

            $blockFinal = null;
            if ($blockTugasAvg !== null || $blockSumatifAvg !== null) {
                $blockFinal = round((0.40 * ($blockTugasAvg ?? 0.0)) + (0.60 * ($blockSumatifAvg ?? 0.0)), 1);
                $finalParts[] = $blockFinal;
            }

            $dailyScores[(string) $blockNum] = [
                'tugas_avg' => $blockTugasAvg,
                'sumatif_avg' => $blockSumatifAvg,
                'final' => $blockFinal,
            ];
        }

        $tugasAvg = $this->calculateComponentAverage($allTugasScores, 1);
        $shAvg = $this->calculateComponentAverage($allSumatifScores, 1);
        $asatScore = $this->calculateComponentAverage($allAsatScores, 1);

        if ($asatScore !== null) {
            $finalParts[] = $asatScore;
        }

        $finalScore = !empty($finalParts)
            ? round(array_sum($finalParts) / count($finalParts), 1)
            : null;

        $hasDaily = collect($dailyScores)->contains(fn ($d) => $d['final'] !== null);
        $hasAsat = $asatScore !== null;

        $status = 'belum_ada_nilai';
        if ($hasDaily && $hasAsat) {
            $status = 'siap_ditinjau';
        } elseif ($hasDaily || $hasAsat) {
            $status = 'belum_lengkap';
        }

        $siswa = Siswa::find($siswaId);

        return SubjectGradeSummary::updateOrCreate(
            [
                'siswa_id' => $siswaId,
                'mata_pelajaran_id' => $mataPelajaranId,
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
            ],
            [
                'kelas' => $siswa?->kelas,
                'uh_avg' => $shAvg,
                'tugas_avg' => $tugasAvg,
                'projek_avg' => $shAvg,
                'daily_scores' => $dailyScores,
                'sh_avg' => $shAvg,
                'asat_score' => $asatScore,
                'sts_score' => $asatScore,
                'final_score' => $finalScore,
                'status' => $status,
            ]
        );
    }

    /**
     * Recalculate Class STS/ASAT Summaries and Competition Ranking (Excel RANK.EQ style)
     */
    public function recalculateClassSTS(string $kelas, int $tahunAjaranId, string $semester = 'Ganjil'): void
    {
        // Check historical cohort enrollment first
        $historicalStudentIds = \App\Models\RiwayatKelasSiswa::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->pluck('siswa_id');

        if ($historicalStudentIds->isNotEmpty()) {
            $students = Siswa::whereIn('id', $historicalStudentIds)->get();
        } else {
            $students = Siswa::where('kelas', $kelas)->get();
        }

        if ($students->isEmpty()) {
            return;
        }

        $classSubjectsCount = MataPelajaran::forClass($kelas, $tahunAjaranId, $semester)->count();
        $totalSubjectsCount = $classSubjectsCount > 0 ? $classSubjectsCount : MataPelajaran::count();
        $studentAverages = [];

        foreach ($students as $siswa) {
            $summaries = SubjectGradeSummary::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->where(function ($q) {
                    $q->whereNotNull('final_score')
                      ->orWhereNotNull('asat_score')
                      ->orWhereNotNull('sts_score');
                })
                ->get();

            $effectiveScores = $summaries
                ->map(fn ($s) => $s->final_score ?? $s->asat_score ?? $s->sts_score)
                ->filter(fn ($v) => $v !== null);

            $completedCount = $effectiveScores->count();
            $totalSts = $completedCount > 0 ? round((float) $effectiveScores->sum(), 1) : null;
            $avgSts = $completedCount > 0 ? round($totalSts / $completedCount, 1) : null;

            $stsSummary = StudentStsSummary::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                ],
                [
                    'kelas' => $kelas,
                    'total_score' => $totalSts,
                    'average_score' => $avgSts,
                    'rank' => null,
                    'completed_subjects_count' => $completedCount,
                    'total_subjects_count' => $totalSubjectsCount,
                    'status' => ($completedCount >= $totalSubjectsCount) ? 'complete' : 'in_progress',
                ]
            );

            $studentAverages[$siswa->id] = $avgSts;
        }

        // Apply competition ranking (RANK.EQ) based on average_score descending
        // Non-null scores are ranked; ties get the same rank, next rank is skipped
        $validAverages = array_filter($studentAverages, fn($val) => $val !== null);
        arsort($validAverages);

        $currentRank = 1;
        $prevScore = null;
        $itemsAtPrevScore = 0;

        foreach ($validAverages as $studentId => $score) {
            if ($prevScore !== null && abs($score - $prevScore) < 0.001) {
                // Same score, keep same rank
                $itemsAtPrevScore++;
            } else {
                $currentRank += $itemsAtPrevScore;
                $itemsAtPrevScore = 1;
                $prevScore = $score;
            }

            StudentStsSummary::where('siswa_id', $studentId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->update(['rank' => $currentRank]);
        }
    }
}
