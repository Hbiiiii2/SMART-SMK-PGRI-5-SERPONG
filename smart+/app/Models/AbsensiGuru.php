<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiGuru extends Model
{
    use HasFactory;

    protected $table = 'absensi_guru';

    protected $fillable = [
        'guru_id',
        'lokasi_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
        'latitude',
        'longitude',
        'foto_lokasi',
        'gps_accuracy',
        'device_token',
        'is_mock_suspect',
        'mock_gps_reason',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i',
        'jam_keluar' => 'datetime:H:i',
        'latitude' => 'float',
        'longitude' => 'float',
        'gps_accuracy' => 'float',
        'is_mock_suspect' => 'boolean',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }
}
