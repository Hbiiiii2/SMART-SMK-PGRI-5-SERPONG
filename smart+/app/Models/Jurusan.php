<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusans';

    protected $fillable = [
        'kode',
        'singkatan',
        'nama',
        'dapodik_jurusan_id',
        'deskripsi',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function rombels()
    {
        return $this->hasMany(Rombel::class, 'jurusan_id');
    }

    public function mataPelajarans()
    {
        return $this->hasMany(MataPelajaran::class, 'jurusan_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Resolve Jurusan model from a rombel / class name (e.g. "X AK" -> AKL, "XI MP 1" -> MPLB).
     */
    public static function resolveFromRombelName(?string $nama): ?self
    {
        $clean = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $nama)));
        if ($clean === '') {
            return null;
        }

        $tokens = preg_split('/[\s_-]+/', $clean) ?: [];
        $all = static::query()->orderBy('id')->get();
        if ($all->isEmpty()) {
            return null;
        }

        foreach ($all as $j) {
            $kode = strtoupper(trim((string) $j->kode));
            $singkatan = strtoupper(trim((string) $j->singkatan));

            if (($singkatan !== '' && in_array($singkatan, $tokens, true))
                || ($kode !== '' && in_array($kode, $tokens, true))) {
                return $j;
            }
        }

        foreach ($all as $j) {
            if (str_contains($clean, strtoupper($j->nama))) {
                return $j;
            }
        }

        return null;
    }
}
