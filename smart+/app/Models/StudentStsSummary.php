<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStsSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'total_score',
        'average_score',
        'rank',
        'completed_subjects_count',
        'total_subjects_count',
        'status',
        'catatan_wali_kelas',
    ];

    protected $casts = [
        'total_score' => 'float',
        'average_score' => 'float',
        'rank' => 'integer',
        'completed_subjects_count' => 'integer',
        'total_subjects_count' => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
}
