<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaProfil extends Model
{
    use HasFactory;

    protected $table = 'siswa_profil';

    protected $fillable = [
        'siswa_id',
        'alamat_jalan',
        'rt',
        'rw',
        'nama_dusun',
        'kelurahan',
        'kecamatan',
        'kode_pos',
        'kode_wilayah_dapodik',
        'jenis_tinggal',
        'alat_transportasi',
        'lintang',
        'bujur',
        'no_registrasi_akta_lahir',
        'skhun',
        'no_peserta_un',
        'no_seri_ijazah',
        'sekolah_asal',
        'kebutuhan_khusus',
        'anak_keberapa',
        'penerima_kps',
        'no_kps',
        'penerima_kip',
        'nomor_kip',
        'nama_di_kip',
        'nomor_kks',
        'layak_pip',
        'alasan_layak_pip',
        'bank',
        'nomor_rekening_bank',
        'rekening_atas_nama',
        'raw_dapodik_payload',
    ];

    protected $casts = [
        'penerima_kps' => 'boolean',
        'penerima_kip' => 'boolean',
        'layak_pip' => 'boolean',
        'anak_keberapa' => 'integer',
        'lintang' => 'float',
        'bujur' => 'float',
        'raw_dapodik_payload' => 'array',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
