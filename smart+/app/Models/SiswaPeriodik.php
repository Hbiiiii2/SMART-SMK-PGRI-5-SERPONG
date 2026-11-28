<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaPeriodik extends Model
{
    use HasFactory;

    protected $table = 'siswa_periodik';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'berat_badan',
        'tinggi_badan',
        'lingkar_kepala',
        'jumlah_saudara_kandung',
        'jarak_rumah_km',
    ];

    protected $casts = [
        'berat_badan' => 'float',
        'tinggi_badan' => 'float',
        'lingkar_kepala' => 'float',
        'jumlah_saudara_kandung' => 'integer',
        'jarak_rumah_km' => 'float',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
