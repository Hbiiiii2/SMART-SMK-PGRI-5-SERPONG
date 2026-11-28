<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Rombel extends Model
{
    use HasFactory;

    protected $table = 'rombels';

    protected $fillable = [
        'dapodik_rombongan_belajar_id',
        'tahun_ajaran_id',
        'semester',
        'nama',
        'tingkat',
        'jurusan_id',
        'kompetensi_keahlian',
        'kurikulum',
        'wali_kelas_guru_id',
        'kapasitas',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'kapasitas' => 'integer',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_guru_id');
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'rombel_id');
    }

    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'rombel_id');
    }

    /**
     * Infer tingkat and kompetensi keahlian from rombel name (e.g. "X AK", "XI MP 1", "XII BD").
     */
    public static function inferMetaFromName(string $nama): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $nama));
        $tingkat = null;
        if (preg_match('/^(XII|XI|IX|VIII|VII|X|\d{1,2})\b/i', $clean, $m)) {
            $tingkat = strtoupper($m[1]);
        }

        $upper = strtoupper($clean);
        $jurusan = match (true) {
            str_contains($upper, 'AKL') || preg_match('/\bAK\b/', $upper) === 1 => 'Akuntansi dan Keuangan Lembaga',
            str_contains($upper, 'BDP') || preg_match('/\bBD\b/', $upper) === 1 => 'Bisnis Digital',
            str_contains($upper, 'MPLB') || str_contains($upper, 'OTKP') || preg_match('/\bMP\b/', $upper) === 1 => 'Manajemen Perkantoran',
            preg_match('/\bRPL\b/', $upper) === 1 || str_contains($upper, 'PPLG') => 'Rekayasa Perangkat Lunak',
            preg_match('/\bTKJ\b/', $upper) === 1 || str_contains($upper, 'TJKT') => 'Teknik Komputer dan Jaringan',
            default => null,
        };

        return [
            'tingkat' => $tingkat,
            'kompetensi_keahlian' => $jurusan,
        ];
    }

    /**
     * Resolve or create a master Rombel record by name & academic year.
     */
    public static function resolveOrCreateByName(string $nama, ?int $tahunAjaranId = null, string $semester = 'Ganjil'): self
    {
        $cleanName = trim(preg_replace('/\s+/', ' ', $nama));
        $taId = $tahunAjaranId
            ?? TahunAjaran::where('is_aktif', true)->value('id')
            ?? TahunAjaran::orderByDesc('id')->value('id');

        if (!$taId) {
            $taId = TahunAjaran::create([
                'nama' => date('Y') . '/' . (date('Y') + 1),
                'tanggal_mulai' => date('Y') . '-07-01',
                'tanggal_selesai' => (date('Y') + 1) . '-06-30',
                'is_aktif' => true,
            ])->id;
        }

        $existing = static::where('tahun_ajaran_id', $taId)
            ->where('semester', $semester)
            ->where('nama', $cleanName)
            ->first();

        if ($existing) {
            if ($existing->jurusan_id === null) {
                $jurusan = Jurusan::resolveFromRombelName($cleanName);
                if ($jurusan) {
                    $existing->update([
                        'jurusan_id' => $jurusan->id,
                        'kompetensi_keahlian' => $existing->kompetensi_keahlian ?: $jurusan->nama,
                    ]);
                }
            }
            return $existing;
        }

        $meta = static::inferMetaFromName($cleanName);
        $jurusan = Jurusan::resolveFromRombelName($cleanName);

        return static::create([
            'tahun_ajaran_id' => $taId,
            'semester' => $semester,
            'nama' => $cleanName,
            'tingkat' => $meta['tingkat'],
            'jurusan_id' => $jurusan?->id,
            'kompetensi_keahlian' => $jurusan?->nama ?? $meta['kompetensi_keahlian'],
            'is_aktif' => true,
        ]);
    }

    /**
     * Return unified class name collection from master Rombel (with legacy Siswa fallback).
     */
    public static function classNames(?int $tahunAjaranId = null, bool $includeStudentFallback = true, bool $excludeAlumni = true): Collection
    {
        $rombelQuery = static::query()->where('is_aktif', true);
        if ($tahunAjaranId) {
            $yearScoped = (clone $rombelQuery)->where('tahun_ajaran_id', $tahunAjaranId)->pluck('nama');
            $names = $yearScoped->isNotEmpty()
                ? $yearScoped
                : $rombelQuery->pluck('nama');
        } else {
            $names = $rombelQuery->pluck('nama');
        }

        if ($includeStudentFallback) {
            $studentQuery = Siswa::query()->whereNotNull('kelas')->where('kelas', '!=', '');
            if ($tahunAjaranId) {
                $studentQuery->where('tahun_ajaran_id', $tahunAjaranId);
            }
            $names = $names->merge($studentQuery->distinct()->pluck('kelas'));
        }

        return $names
            ->map(fn ($n) => trim((string) $n))
            ->filter(function ($n) use ($excludeAlumni) {
                if ($n === '') {
                    return false;
                }
                if (!$excludeAlumni) {
                    return true;
                }
                $lower = strtolower($n);
                return !str_starts_with($lower, 'alumni') && !str_starts_with($lower, 'lulus');
            })
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Keep E-Rapor ClassHomeroom synchronized when wali kelas is assigned on a Rombel.
     */
    public function syncHomeroom(): void
    {
        if (!$this->wali_kelas_guru_id) {
            return;
        }

        $guru = Guru::find($this->wali_kelas_guru_id);
        if (!$guru) {
            return;
        }

        ClassHomeroom::updateOrCreate(
            [
                'kelas' => $this->nama,
                'tahun_ajaran_id' => $this->tahun_ajaran_id,
            ],
            [
                'guru_id' => $guru->id,
                'nama_wali_kelas' => $guru->nama_lengkap,
                'nip_wali_kelas' => $guru->nip,
            ]
        );
    }
}
