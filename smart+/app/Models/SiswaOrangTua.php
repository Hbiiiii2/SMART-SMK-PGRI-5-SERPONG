<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaOrangTua extends Model
{
    use HasFactory;

    protected $table = 'siswa_orang_tua';

    protected $fillable = [
        'siswa_id',
        'hubungan',
        'nama',
        'nik',
        'tahun_lahir',
        'jenjang_pendidikan',
        'pekerjaan',
        'penghasilan',
        'no_hp',
        'status_hidup',
    ];

    protected $casts = [
        'tahun_lahir' => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function getHubunganLabelAttribute(): string
    {
        return match ($this->hubungan) {
            'ayah' => 'Ayah Kandung',
            'ibu' => 'Ibu Kandung',
            'wali' => 'Wali',
            default => ucfirst((string) $this->hubungan),
        };
    }
}
