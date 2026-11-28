<?php

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\AbsensiStaff;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\StaffAkademik;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('auto marks active siswa, guru, and staff alpha after GPS cutoff via attendance:mark-alpha while skipping alumni and present users', function () {
    $ta = TahunAjaran::factory()->aktif()->create();
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMKS PGRI 5 Serpong',
        'jam_masuk' => '12:30:00',
        'jam_pulang' => '17:20:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30, // GPS cutoff = 13:00
        'radius_absensi_meter' => 100,
        'metode_absensi_siswa' => 'Zonasi',
        'metode_absensi_guru' => 'Zonasi',
        'metode_absensi_staff' => 'Zonasi',
        'izinkan_absen_tanpa_jadwal' => true,
        'absensi_hari_sabtu' => true,
        'absensi_hari_minggu' => false,
    ]);

    // Monday 13:05 (past 13:00 GPS cutoff, before 17:50 RFID cutoff)
    Carbon::setTestNow(Carbon::parse('2026-11-02 13:05:00', 'Asia/Jakarta'));

    $jadwal = Jadwal::factory()->create([
        'kelas' => 'XI MP 1',
        'hari' => 'Senin',
        'tahun_ajaran_id' => $ta->id,
    ]);

    $guruAbsent = Guru::factory()->create(['user_id' => User::factory()->guru()->create()->id]);
    $guruPresent = Guru::factory()->create(['user_id' => User::factory()->guru()->create()->id]);
    AbsensiGuru::create([
        'guru_id' => $guruPresent->id,
        'tanggal' => '2026-11-02',
        'jam_masuk' => '12:25:00',
        'status' => 'Hadir',
    ]);

    $siswaAbsent = Siswa::factory()->create(['kelas' => 'XI MP 1', 'tahun_ajaran_id' => $ta->id, 'status_siswa' => 'aktif']);
    $siswaPresent = Siswa::factory()->create(['kelas' => 'XI MP 1', 'tahun_ajaran_id' => $ta->id, 'status_siswa' => 'aktif']);
    $siswaAlumni = Siswa::factory()->create(['kelas' => 'XI MP 1', 'tahun_ajaran_id' => $ta->id, 'status_siswa' => 'lulus', 'tahun_lulus' => 2026]);
    AbsensiSiswa::create([
        'siswa_id' => $siswaPresent->id,
        'jadwal_id' => $jadwal->id,
        'tanggal' => '2026-11-02',
        'jam_masuk' => '12:28:00',
        'status' => 'Hadir',
    ]);

    $staffUser = User::factory()->create(['role' => 'staff_akademik', 'is_active' => true]);
    $staffAbsent = StaffAkademik::create([
        'pengguna_id' => $staffUser->id,
        'nama_lengkap' => 'Staff Absent',
        'jenis_kelamin' => 'L',
        'status_aktif' => true,
    ]);

    $this->artisan('attendance:mark-alpha')->assertExitCode(0);

    expect(AbsensiGuru::where('guru_id', $guruAbsent->id)->whereDate('tanggal', '2026-11-02')->value('status'))->toBe('Alpha');
    expect(AbsensiGuru::where('guru_id', $guruPresent->id)->whereDate('tanggal', '2026-11-02')->value('status'))->toBe('Hadir');

    expect(AbsensiSiswa::where('siswa_id', $siswaAbsent->id)->where('jadwal_id', $jadwal->id)->value('status'))->toBe('Alfa');
    expect(AbsensiSiswa::where('siswa_id', $siswaPresent->id)->where('jadwal_id', $jadwal->id)->value('status'))->toBe('Hadir');
    expect(AbsensiSiswa::where('siswa_id', $siswaAlumni->id)->exists())->toBeFalse();

    expect(AbsensiStaff::where('staff_akademik_id', $staffAbsent->id)->whereDate('tanggal', '2026-11-02')->value('status'))->toBe('Alfa');
});

it('respects absensi_hari_sabtu and hari libur in attendance:mark-alpha', function () {
    $ta = TahunAjaran::factory()->aktif()->create();
    $settings = PengaturanSekolah::create([
        'nama_sekolah' => 'SMKS PGRI 5 Serpong',
        'jam_masuk' => '07:00:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'metode_absensi_siswa' => 'Zonasi',
        'metode_absensi_guru' => 'Zonasi',
        'metode_absensi_staff' => 'Zonasi',
        'absensi_hari_sabtu' => true,
        'absensi_hari_minggu' => false,
    ]);

    $guru = Guru::factory()->create(['user_id' => User::factory()->guru()->create()->id]);
    $siswa = Siswa::factory()->create(['kelas' => 'X AK', 'tahun_ajaran_id' => $ta->id, 'status_siswa' => 'aktif']);
    Jadwal::factory()->create(['kelas' => 'X AK', 'hari' => 'Sabtu', 'tahun_ajaran_id' => $ta->id]);

    // Saturday 08:00 with absensi_hari_sabtu = true -> marks Alfa
    Carbon::setTestNow(Carbon::parse('2026-11-07 08:00:00', 'Asia/Jakarta'));
    $this->artisan('attendance:mark-alpha')->assertExitCode(0);

    expect(AbsensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', '2026-11-07')->value('status'))->toBe('Alpha');
    expect(AbsensiSiswa::where('siswa_id', $siswa->id)->whereDate('tanggal', '2026-11-07')->value('status'))->toBe('Alfa');

    // Monday national holiday -> skipped
    Carbon::setTestNow(Carbon::parse('2026-11-09 08:00:00', 'Asia/Jakarta'));
    HariLibur::create(['nama' => 'Hari Libur Uji', 'tanggal' => '2026-11-09', 'keterangan' => 'Hari Libur Uji']);
    $this->artisan('attendance:mark-alpha')->assertExitCode(0);

    expect(AbsensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', '2026-11-09')->exists())->toBeFalse();
    expect(AbsensiSiswa::where('siswa_id', $siswa->id)->whereDate('tanggal', '2026-11-09')->exists())->toBeFalse();
});

it('auto marks siswa and guru alfa on late GPS check-in even when outside radius or without photo', function () {
    $ta = TahunAjaran::factory()->aktif()->create();
    Lokasi::factory()->create([
        'nama' => 'Kampus Utama',
        'latitude' => -6.3021000,
        'longitude' => 106.6522000,
        'radius' => 100,
    ]);

    PengaturanSekolah::create([
        'nama_sekolah' => 'SMKS PGRI 5 Serpong',
        'jam_masuk' => '07:00:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30, // Cutoff 07:30
        'metode_absensi_siswa' => 'Zonasi',
        'metode_absensi_guru' => 'Zonasi',
        'metode_absensi_staff' => 'Zonasi',
        'mode_lokasi_gps' => 'semua_lokasi',
        'validasi_radius_gps' => true,
        'wajib_foto_gps' => true,
        'izinkan_absen_tanpa_jadwal' => true,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-02 07:45:00', 'Asia/Jakarta'));

    $siswaUser = User::factory()->siswa()->create(['is_active' => true]);
    $siswa = Siswa::factory()->create(['user_id' => $siswaUser->id, 'kelas' => 'X AK', 'tahun_ajaran_id' => $ta->id]);

    $guruUser = User::factory()->guru()->create(['is_active' => true]);
    $guru = Guru::factory()->create(['user_id' => $guruUser->id]);

    // Late check-in WITHOUT photo and FAR outside radius (-6.10, 106.90)
    $this->actingAs($siswaUser)->postJson('/api/gps/check-in', [
        'latitude' => -6.100000,
        'longitude' => 106.900000,
    ])->assertStatus(400)->assertJson(['success' => false, 'status' => 'Alfa']);

    $this->actingAs($guruUser)->postJson('/api/gps/check-in', [
        'latitude' => -6.100000,
        'longitude' => 106.900000,
    ])->assertStatus(400)->assertJson(['success' => false, 'status' => 'Alfa']);

    expect(AbsensiSiswa::where('siswa_id', $siswa->id)->whereDate('tanggal', '2026-11-02')->value('status'))->toBe('Alfa');
    expect(AbsensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', '2026-11-02')->value('status'))->toBe('Alpha');
});

it('auto marks active siswa without jadwal as daily alfa when izinkan_absen_tanpa_jadwal is enabled', function () {
    $ta = TahunAjaran::factory()->aktif()->create();
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMKS PGRI 5 Serpong',
        'jam_masuk' => '07:00:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'metode_absensi_siswa' => 'Zonasi',
        'metode_absensi_guru' => 'Zonasi',
        'metode_absensi_staff' => 'Zonasi',
        'izinkan_absen_tanpa_jadwal' => true,
    ]);

    $siswaNoJadwal = Siswa::factory()->create(['kelas' => 'X BD', 'tahun_ajaran_id' => $ta->id, 'status_siswa' => 'aktif']);

    Carbon::setTestNow(Carbon::parse('2026-11-02 07:35:00', 'Asia/Jakarta'));
    $this->artisan('attendance:mark-alpha')->assertExitCode(0);

    $rec = AbsensiSiswa::where('siswa_id', $siswaNoJadwal->id)->whereDate('tanggal', '2026-11-02')->first();
    expect($rec)->not->toBeNull();
    expect($rec->jadwal_id)->toBeNull();
    expect($rec->status)->toBe('Alfa');
});
