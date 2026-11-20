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
    public function calculateComponentAverage(array $scores, int $decimals = 2): ?float
    {
        $validScores = [];

        foreach ($scores as $score) {
            if ($score !== null && is_numeric($score)) {
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
     * Recalculate a student's subject grade summary for a given period
     */
    public function recalculateSubjectGrade(
        int $siswaId,
        int $mataPelajaranId,
        int $tahunAjaranId,
        string $semester = 'Ganjil'
    ): SubjectGradeSummary {
        // Fetch components
        $components = AssessmentComponent::where('is_active', true)->get()->keyBy('code');

        // Fetch all assessment items for this subject and period
        $items = AssessmentItem::where('mata_pelajaran_id', $mataPelajaranId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get();

        $itemIds = $items->pluck('id');
        $scoresByItemId = StudentAssessmentScore::where('siswa_id', $siswaId)
            ->whereIn('assessment_item_id', $itemIds)
            ->pluck('score', 'assessment_item_id');

        $componentScores = [
            'uh' => [],
            'tugas' => [],
            'praktek' => [],
            'projek' => [],
            'portofolio' => [],
            'sts' => [],
        ];

        foreach ($items as $item) {
            $code = $item->component?->code ?? 'tugas';
            if (isset($componentScores[$code])) {
                $componentScores[$code][] = $scoresByItemId[$item->id] ?? null;
            }
        }

        $uhAvg = $this->calculateComponentAverage($componentScores['uh']);
        $tugasAvg = $this->calculateComponentAverage($componentScores['tugas']);
        $praktekAvg = $this->calculateComponentAverage($componentScores['praktek']);
        $projekAvg = $this->calculateComponentAverage($componentScores['projek']);
        $portofolioAvg = $this->calculateComponentAverage($componentScores['portofolio']);
        $stsScore = $this->calculateComponentAverage($componentScores['sts']);

        // Determine completion status
        $filledComponents = 0;
        foreach ([$uhAvg, $tugasAvg, $praktekAvg, $projekAvg, $portofolioAvg, $stsScore] as $val) {
            if ($val !== null) {
                $filledComponents++;
            }
        }

        $status = 'belum_ada_nilai';
        if ($filledComponents > 0 && $filledComponents < 3) {
            $status = 'belum_lengkap';
        } elseif ($filledComponents >= 3) {
            $status = 'siap_ditinjau';
        }

        // Weighted final score calculation (if configured)
        // Default PRD weight: UH 20%, Tugas 20%, Praktek 15%, Projek 15%, Portofolio 10%, STS 20%
        $activeWeights = [];
        $weightedSum = 0;
        $totalWeight = 0;

        $compVals = [
            'uh' => $uhAvg,
            'tugas' => $tugasAvg,
            'praktek' => $praktekAvg,
            'projek' => $projekAvg,
            'portofolio' => $portofolioAvg,
            'sts' => $stsScore,
        ];

        foreach ($compVals as $code => $avgVal) {
            if ($avgVal !== null && isset($components[$code])) {
                $weight = $components[$code]->default_weight;
                $weightedSum += ($avgVal * $weight);
                $totalWeight += $weight;
            }
        }

        $finalScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;

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
                'uh_avg' => $uhAvg,
                'tugas_avg' => $tugasAvg,
                'praktek_avg' => $praktekAvg,
                'projek_avg' => $projekAvg,
                'portofolio_avg' => $portofolioAvg,
                'sts_score' => $stsScore,
                'final_score' => $finalScore,
                'status' => $status,
            ]
        );
    }

    /**
     * Recalculate Class STS Summaries and Competition Ranking (Excel RANK.EQ style)
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

        $totalSubjectsCount = MataPelajaran::count();
        $studentAverages = [];

        foreach ($students as $siswa) {
            $summaries = SubjectGradeSummary::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->where(function ($q) {
                    $q->whereNotNull('sts_score')
                      ->orWhereNotNull('final_score');
                })
                ->get();

            $effectiveScores = $summaries
                ->map(fn($s) => $s->sts_score ?? $s->final_score)
                ->filter(fn($v) => $v !== null);

            $completedCount = $effectiveScores->count();
            $totalSts = $completedCount > 0 ? (float) $effectiveScores->sum() : null;
            $avgSts = $completedCount > 0 ? round($totalSts / $completedCount, 2) : null;

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
