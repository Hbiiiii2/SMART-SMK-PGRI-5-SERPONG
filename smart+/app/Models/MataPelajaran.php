<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MataPelajaran extends Model
{
    use HasFactory;

    protected $table = 'mata_pelajaran';

    public const KELOMPOK_OPTIONS = [
        'umum' => 'Kelompok A - Mata Pelajaran Umum',
        'kejuruan' => 'Kelompok B - Mata Pelajaran Kejuruan',
        'konsentrasi' => 'Kelompok C - Konsentrasi Keahlian',
        'pilihan' => 'Kelompok D - Mata Pelajaran Pilihan',
        'mulok' => 'Muatan Lokal',
    ];

    public const KELOMPOK_ORDER = [
        'umum' => 1,
        'kejuruan' => 2,
        'konsentrasi' => 3,
        'pilihan' => 4,
        'mulok' => 5,
    ];

    protected $fillable = [
        'kode',
        'nama',
        'kelompok',
        'jurusan_id',
        'tingkat',
        'urutan',
        'tampil_di_rapor',
        'kkm',
        'deskripsi',
    ];

    protected $casts = [
        'kkm' => 'float',
        'tingkat' => 'array',
        'urutan' => 'integer',
        'tampil_di_rapor' => 'boolean',
    ];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }

    public function tugas()
    {
        return $this->hasMany(Tugas::class);
    }

    public function getKelompokLabelAttribute(): string
    {
        return self::KELOMPOK_OPTIONS[$this->kelompok ?? 'umum'] ?? self::KELOMPOK_OPTIONS['umum'];
    }

    public function getTingkatLabelAttribute(): string
    {
        $levels = is_array($this->tingkat) ? array_filter($this->tingkat) : [];
        return empty($levels) ? 'Semua Tingkat' : implode(', ', $levels);
    }

    /**
     * Resolve subjects relevant to a specific class (major + level + scheduled/graded subjects),
     * sorted cleanly by kelompok, urutan, and name.
     */
    public static function forClass(
        ?string $kelas,
        ?int $tahunAjaranId = null,
        ?string $semester = null,
        ?int $siswaId = null
    ): Collection {
        $all = static::with('jurusan')->where('tampil_di_rapor', true)->get();
        if ($all->isEmpty() || empty(trim((string) $kelas))) {
            return self::sortSubjects($all);
        }

        $cleanKelas = trim((string) $kelas);

        $rombel = Rombel::query()
            ->where('nama', $cleanKelas)
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->first()
            ?? Rombel::where('nama', $cleanKelas)->orderByDesc('tahun_ajaran_id')->first();

        $jurusanId = $rombel?->jurusan_id ?? Jurusan::resolveFromRombelName($cleanKelas)?->id;
        $tingkat = $rombel?->tingkat ?? Rombel::inferMetaFromName($cleanKelas)['tingkat'];

        $gradedQuery = SubjectGradeSummary::query();
        if ($siswaId) {
            $gradedQuery->where('siswa_id', $siswaId);
        } else {
            $gradedQuery->where('kelas', $cleanKelas);
        }
        if ($tahunAjaranId) {
            $gradedQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }
        if ($semester) {
            $gradedQuery->where('semester', $semester);
        }
        $gradedIds = $gradedQuery->distinct()->pluck('mata_pelajaran_id')->filter()->all();

        $hasScopingRules = $all->contains(function (self $mp) {
            $levels = is_array($mp->tingkat) ? array_filter($mp->tingkat) : [];
            return $mp->jurusan_id !== null || !empty($levels);
        });

        if ($hasScopingRules) {
            $filtered = $all->filter(function (self $mp) use ($gradedIds, $jurusanId, $tingkat) {
                if (in_array($mp->id, $gradedIds, true)) {
                    return true;
                }

                $majorOk = ($mp->jurusan_id === null) || ($jurusanId !== null && $mp->jurusan_id === $jurusanId);
                $levels = is_array($mp->tingkat) ? array_filter($mp->tingkat) : [];
                $levelOk = empty($levels) || $tingkat === null || in_array($tingkat, $levels, true);

                return $majorOk && $levelOk;
            });

            return self::sortSubjects($filtered);
        }

        $raporSubjectIds = $all->pluck('id')->all();
        $scheduledIds = Jadwal::where('kelas', $cleanKelas)
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereIn('mata_pelajaran_id', $raporSubjectIds)
            ->distinct()
            ->pluck('mata_pelajaran_id')
            ->filter()
            ->all();

        if (!empty($scheduledIds)) {
            $filtered = $all->filter(fn (self $mp) => in_array($mp->id, $scheduledIds, true) || in_array($mp->id, $gradedIds, true));
            return self::sortSubjects($filtered);
        }

        return self::sortSubjects($all);
    }

    /**
     * Resolve printed report card section heading for a subject group.
     */
    public static function resolveKelompokHeading(
        string $kelompok,
        ?Jurusan $classJurusan = null,
        ?Jurusan $subjectJurusan = null
    ): string {
        if ($kelompok === 'umum') {
            return 'A. KELOMPOK MATA PELAJARAN UMUM';
        }
        if ($kelompok === 'kejuruan') {
            return 'B. KELOMPOK MATA PELAJARAN KEJURUAN';
        }
        if ($kelompok === 'konsentrasi') {
            $j = $subjectJurusan ?? $classJurusan;
            $kode = strtoupper(trim((string) ($j?->kode ?? '')));
            return match ($kode) {
                'MPLB' => 'C. KONSENTRASI KEAHLIAN MANAJEMEN PERKANTORAN',
                'AKL' => 'C. KONSENTRASI KEAHLIAN AKUNTANSI',
                'BD' => 'C. KONSENTRASI KEAHLIAN BISNIS DIGITAL',
                default => $j?->nama
                    ? 'C. KONSENTRASI KEAHLIAN ' . strtoupper($j->nama)
                    : 'C. KONSENTRASI KEAHLIAN',
            };
        }
        if ($kelompok === 'pilihan') {
            return 'D. MATA PELAJARAN PILIHAN';
        }
        if ($kelompok === 'mulok') {
            return 'E. MUATAN LOKAL';
        }

        return strtoupper($kelompok);
    }

    /**
     * Resolve union of all subjects across a student's academic periods & recorded grades.
     */
    public static function forStudentHistory(Siswa $siswa, Collection $periods): Collection
    {
        $collected = collect();

        foreach ($periods as $p) {
            $k = $p->kelas ?? $siswa->kelas;
            $taId = $p->tahun_ajaran_id ?? $siswa->tahun_ajaran_id;
            $sem = $p->semester ?? null;
            if (!empty($k)) {
                $collected = $collected->merge(self::forClass($k, $taId ? (int) $taId : null, $sem, $siswa->id));
            }
        }

        $gradedIds = SubjectGradeSummary::where('siswa_id', $siswa->id)
            ->distinct()
            ->pluck('mata_pelajaran_id')
            ->filter()
            ->all();

        if (!empty($gradedIds)) {
            $collected = $collected->merge(static::with('jurusan')->whereIn('id', $gradedIds)->get());
        }

        if ($collected->isEmpty()) {
            return self::sortSubjects(static::with('jurusan')->get());
        }

        return self::sortSubjects($collected->unique('id'));
    }

    public static function sortSubjects(Collection $subjects): Collection
    {
        return $subjects->sort(function (self $a, self $b) {
            $ga = self::KELOMPOK_ORDER[$a->kelompok ?? 'umum'] ?? 99;
            $gb = self::KELOMPOK_ORDER[$b->kelompok ?? 'umum'] ?? 99;
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }

            $ua = $a->urutan ?? 100;
            $ub = $b->urutan ?? 100;
            if ($ua !== $ub) {
                return $ua <=> $ub;
            }

            return strcasecmp((string) $a->nama, (string) $b->nama);
        })->values();
    }
}
