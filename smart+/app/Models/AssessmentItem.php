<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_component_id',
        'daily_block',
        'weight_group',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'semester',
        'kelas',
        'sequence',
        'title',
        'max_score',
        'tugas_id',
        'cbt_exam_external_id',
    ];

    protected $casts = [
        'daily_block' => 'integer',
        'max_score' => 'float',
        'sequence' => 'integer',
    ];

    /**
     * Resolve weight group ('tugas', 'sumatif', or 'asat') from explicit column or component code.
     */
    public function resolveWeightGroup(): string
    {
        if (!empty($this->weight_group)) {
            return $this->weight_group;
        }

        $code = strtolower((string) ($this->component?->code ?? ''));
        if ($code === 'tugas') {
            return 'tugas';
        }
        if (in_array($code, ['asat', 'sts'], true)) {
            return 'asat';
        }

        return 'sumatif';
    }

    /**
     * Resolve daily block index (1..n) for daily items, or null for ASAT items.
     */
    public function resolveDailyBlock(): ?int
    {
        if ($this->resolveWeightGroup() === 'asat') {
            return null;
        }

        return $this->daily_block ?? 1;
    }

    public function component()
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function lmsTugas()
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function scores()
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }
}
