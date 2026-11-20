<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'absensi_siswa';

    protected $fillable = [
        'siswa_id',
        'jadwal_id',
        'lokasi_id',
        'tanggal',
        'status',
        'jam_masuk',
        'jam_keluar',
        'keterangan',
        'diedit_oleh',
        'alasan_perubahan',
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

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }
}
