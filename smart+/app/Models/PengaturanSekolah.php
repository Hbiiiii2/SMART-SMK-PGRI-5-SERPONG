<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanSekolah extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_sekolah';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'alamat_sekolah',
        'no_telepon_sekolah',
        'email_sekolah',
        'logo',
        'jam_masuk',
        'jam_pulang',
        'toleransi_terlambat_menit',
        'toleransi_alpha_menit',
        'radius_absensi_meter',
        'metode_absensi_siswa',
        'metode_absensi_guru',
        'metode_absensi_staff',
        'mode_lokasi_gps',
        'validasi_radius_gps',
        'wajib_foto_gps',
        'izinkan_absen_tanpa_jadwal',
        'absensi_hari_sabtu',
        'absensi_hari_minggu',
        'mode_anti_mock_gps',
        'maks_akurasi_gps_meter',
        'mode_deteksi_perangkat_bersama',
        'batas_akun_per_perangkat',
        'dapodik_url',
        'dapodik_token',
        'dapodik_semester_id',
        'dapodik_auto_sync',
        'dapodik_last_sync_at',
    ];

    protected $casts = [
        'toleransi_terlambat_menit' => 'integer',
        'toleransi_alpha_menit' => 'integer',
        'radius_absensi_meter' => 'float',
        'validasi_radius_gps' => 'boolean',
        'wajib_foto_gps' => 'boolean',
        'izinkan_absen_tanpa_jadwal' => 'boolean',
        'absensi_hari_sabtu' => 'boolean',
        'absensi_hari_minggu' => 'boolean',
        'maks_akurasi_gps_meter' => 'integer',
        'batas_akun_per_perangkat' => 'integer',
        'dapodik_token' => 'encrypted',
        'dapodik_auto_sync' => 'boolean',
        'dapodik_last_sync_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'nama_sekolah' => 'SMK PGRI 5 Serpong',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => true,
            'izinkan_absen_tanpa_jadwal' => true,
            'absensi_hari_sabtu' => false,
            'absensi_hari_minggu' => false,
            'mode_anti_mock_gps' => 'peringatan',
            'maks_akurasi_gps_meter' => 100,
            'mode_deteksi_perangkat_bersama' => 'tandai',
            'batas_akun_per_perangkat' => 2,
        ]);
    }

    public function getMethodForRole(string $role): string
    {
        return match ($role) {
            'siswa' => $this->metode_absensi_siswa ?? 'RFID',
            'guru' => $this->metode_absensi_guru ?? 'RFID',
            default => $this->metode_absensi_staff ?? 'RFID',
        };
    }

    public function allowsGpsForRole(string $role): bool
    {
        return in_array($this->getMethodForRole($role), ['Zonasi', 'Hybrid'], true);
    }

    public function allowsRfidForRole(string $role): bool
    {
        return in_array($this->getMethodForRole($role), ['RFID', 'Hybrid'], true);
    }

    public function isWeekendHoliday(Carbon $date): bool
    {
        if ($date->dayOfWeek === Carbon::SATURDAY) {
            return !($this->absensi_hari_sabtu ?? false);
        }

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return !($this->absensi_hari_minggu ?? false);
        }

        return false;
    }

    public function getGpsAlphaCutoff(Carbon $date): Carbon
    {
        $terlambatMenit = (int) ($this->toleransi_terlambat_menit ?? 15);
        $alphaRaw = (int) ($this->toleransi_alpha_menit ?? 30);
        $alphaMenit = $alphaRaw > $terlambatMenit ? $alphaRaw : ($terlambatMenit + max(15, $alphaRaw));

        return Carbon::parse($date->toDateString() . ' ' . ($this->jam_masuk ?: '07:00:00'), 'Asia/Jakarta')
            ->addMinutes($alphaMenit);
    }

    public function getRfidAlphaCutoff(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString() . ' ' . ($this->jam_pulang ?: '15:00:00'), 'Asia/Jakarta')
            ->addMinutes((int) ($this->toleransi_alpha_menit ?? 30));
    }

    public function getEffectiveAlphaCutoffForRole(string $role, Carbon $date): Carbon
    {
        return $this->allowsGpsForRole($role)
            ? $this->getGpsAlphaCutoff($date)
            : $this->getRfidAlphaCutoff($date);
    }
}
