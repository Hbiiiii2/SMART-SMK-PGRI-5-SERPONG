<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'user_id',
        'dapodik_peserta_didik_id',
        'dapodik_registrasi_id',
        'nipd',
        'nisn',
        'nik',
        'no_kk',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'agama_id',
        'kelas',
        'rombel_id',
        'alamat',
        'no_telepon',
        'telepon_rumah',
        'no_wa_aktif',
        'email_pribadi',
        'foto',
        'tahun_ajaran_id',
        'status_siswa',
        'tahun_lulus',
        'tahun_ajaran_lulus_id',
        'tanggal_masuk',
        'tanggal_keluar',
        'alasan_keluar',
        'sync_source',
        'last_synced_at',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'tanggal_keluar' => 'date',
        'last_synced_at' => 'datetime',
        'agama_id' => 'integer',
        'tahun_lulus' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rombel()
    {
        return $this->belongsTo(Rombel::class, 'rombel_id');
    }

    public function profil()
    {
        return $this->hasOne(SiswaProfil::class, 'siswa_id');
    }

    public function orangTuas()
    {
        return $this->hasMany(SiswaOrangTua::class, 'siswa_id');
    }

    public function ayah()
    {
        return $this->hasOne(SiswaOrangTua::class, 'siswa_id')->where('hubungan', 'ayah');
    }

    public function ibu()
    {
        return $this->hasOne(SiswaOrangTua::class, 'siswa_id')->where('hubungan', 'ibu');
    }

    public function wali()
    {
        return $this->hasOne(SiswaOrangTua::class, 'siswa_id')->where('hubungan', 'wali');
    }

    public function periodiks()
    {
        return $this->hasMany(SiswaPeriodik::class, 'siswa_id')->orderByDesc('tahun_ajaran_id')->orderByDesc('semester');
    }

    public function latestPeriodik()
    {
        return $this->hasOne(SiswaPeriodik::class, 'siswa_id')->latestOfMany('id');
    }

    public function rfidCards()
    {
        return $this->hasMany(RfidCard::class);
    }

    public function absensiSiswas()
    {
        return $this->hasMany(AbsensiSiswa::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function tahunAjaranLulus()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_lulus_id');
    }

    public function raporArchives()
    {
        return $this->hasMany(RaporArchive::class, 'siswa_id');
    }

    public function pengumpulanTugas()
    {
        return $this->hasMany(PengumpulanTugas::class);
    }

    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'siswa_id')->orderBy('tahun_ajaran_id')->orderBy('semester');
    }

    public function gradeSummaries()
    {
        return $this->hasMany(SubjectGradeSummary::class, 'siswa_id');
    }

    public function stsSummaries()
    {
        return $this->hasMany(StudentStsSummary::class, 'siswa_id');
    }

    public function assessmentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class, 'siswa_id');
    }

    public function scopeAktif($query)
    {
        return $query->where(function ($q) {
            $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
        });
    }

    public function getIsAlumniAttribute(): bool
    {
        if (in_array($this->status_siswa, ['lulus', 'mutasi_keluar', 'drop_out', 'mengundurkan_diri', 'wafat'], true)) {
            return true;
        }

        $k = strtolower(trim($this->kelas ?? ''));
        return str_starts_with($k, 'alumni') || str_starts_with($k, 'lulus');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_siswa ?? 'aktif') {
            'aktif' => 'Aktif',
            'lulus' => $this->tahun_lulus ? "Lulus / Alumni {$this->tahun_lulus}" : 'Lulus / Alumni',
            'mutasi_keluar' => 'Mutasi Keluar',
            'drop_out' => 'Drop Out',
            'mengundurkan_diri' => 'Mengundurkan Diri',
            'wafat' => 'Wafat',
            default => ucfirst(str_replace('_', ' ', (string) $this->status_siswa)),
        };
    }

    public function getAlumniLabelAttribute(): string
    {
        return $this->tahun_lulus ? "Alumni {$this->tahun_lulus}" : 'Alumni';
    }

    public function getDataCompletenessAttribute(): array
    {
        $profil = $this->relationLoaded('profil') ? $this->profil : $this->profil()->first();
        $ortus = $this->relationLoaded('orangTuas') ? $this->orangTuas : $this->orangTuas()->get();
        $ibu = $ortus->firstWhere('hubungan', 'ibu');

        $checks = [
            'NISN' => !empty($this->nisn),
            'NIPD' => !empty($this->nipd),
            'NIK 16 Digit' => !empty($this->nik) && preg_match('/^\d{16}$/', (string) $this->nik) === 1,
            'Tempat Lahir' => !empty($this->tempat_lahir),
            'Tanggal Lahir' => !empty($this->tanggal_lahir),
            'Jenis Kelamin' => in_array($this->jenis_kelamin, ['L', 'P'], true),
            'Agama' => !empty($this->agama),
            'Alamat & Wilayah' => !empty($this->alamat) && !empty($profil?->kelurahan) && !empty($profil?->kecamatan),
            'Nama Ibu Kandung' => !empty($ibu?->nama),
            'Koordinat Rumah' => $profil !== null
                && $profil->lintang !== null
                && $profil->bujur !== null
                && ($profil->lintang != 0.0 || $profil->bujur != 0.0)
                && $profil->lintang >= -11.5 && $profil->lintang <= 6.5
                && $profil->bujur >= 94.0 && $profil->bujur <= 142.0,
        ];

        $filled = count(array_filter($checks));
        $total = count($checks);
        $issues = array_keys(array_filter($checks, fn ($ok) => !$ok));

        return [
            'score' => (int) round(($filled / $total) * 100),
            'filled' => $filled,
            'total' => $total,
            'issues' => $issues,
        ];
    }

    public static function maskSensitive(?string $value): string
    {
        $val = trim((string) $value);
        if ($val === '') {
            return '-';
        }
        $len = strlen($val);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($val, 0, 4) . str_repeat('*', $len - 6) . substr($val, -2);
    }
}
