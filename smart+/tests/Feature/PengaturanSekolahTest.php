<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\AbsensiStaff;
use App\Models\Guru;
use App\Models\Lokasi;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\StaffAkademik;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengaturanSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PengaturanSekolah::create([
            'nama_sekolah' => 'Sekolah Test',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
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
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_super_admin_can_update_attendance_methods()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'Sekolah Update',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'toleransi_terlambat_menit' => 10,
            'toleransi_alpha_menit' => 45,
            'radius_absensi_meter' => 50,
            'metode_absensi_siswa' => 'Hybrid',
            'metode_absensi_guru' => 'Zonasi',
            'metode_absensi_staff' => 'Hybrid',
            'mode_lokasi_gps' => 'khusus_pengguna',
            'validasi_radius_gps' => '0',
            'wajib_foto_gps' => '0',
            'izinkan_absen_tanpa_jadwal' => '1',
            'absensi_hari_sabtu' => '1',
            'absensi_hari_minggu' => '0',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseHas('pengaturan_sekolah', [
            'nama_sekolah' => 'Sekolah Update',
            'metode_absensi_siswa' => 'Hybrid',
            'metode_absensi_guru' => 'Zonasi',
            'metode_absensi_staff' => 'Hybrid',
            'mode_lokasi_gps' => 'khusus_pengguna',
            'validasi_radius_gps' => 0,
            'wajib_foto_gps' => 0,
            'izinkan_absen_tanpa_jadwal' => 1,
            'absensi_hari_sabtu' => 1,
            'absensi_hari_minggu' => 0,
        ]);
    }

    public function test_admin_sekolah_can_also_update_attendance_methods_and_gps_rules()
    {
        $admin = User::factory()->create([
            'role' => 'admin_sekolah',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'SMK PGRI 5 Serpong',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 150,
            'metode_absensi_siswa' => 'Zonasi',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => '1',
            'wajib_foto_gps' => '0',
            'izinkan_absen_tanpa_jadwal' => '1',
            'absensi_hari_sabtu' => '1',
            'absensi_hari_minggu' => '0',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseHas('pengaturan_sekolah', [
            'nama_sekolah' => 'SMK PGRI 5 Serpong',
            'metode_absensi_siswa' => 'Zonasi',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
            'mode_lokasi_gps' => 'semua_lokasi',
            'wajib_foto_gps' => 0,
            'absensi_hari_sabtu' => 1,
        ]);
    }

    public function test_validation_fails_for_invalid_methods()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'Sekolah Update',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'toleransi_terlambat_menit' => 10,
            'toleransi_alpha_menit' => 45,
            'radius_absensi_meter' => 50,
            'metode_absensi_siswa' => 'Invalid',
            'metode_absensi_guru' => 'Invalid',
            'metode_absensi_staff' => 'Invalid',
        ]);

        $response->assertSessionHasErrors(['metode_absensi_siswa', 'metode_absensi_guru', 'metode_absensi_staff']);
    }

    public function test_siswa_guru_and_staff_can_use_gps_attendance_based_on_pengaturan_sekolah()
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:10:00', 'Asia/Jakarta'));

        $lokasi = Lokasi::factory()->create([
            'nama' => 'Kampus Utama',
            'latitude' => -6.3021000,
            'longitude' => 106.6522000,
            'radius' => 100,
        ]);

        $pengaturan = PengaturanSekolah::first();
        $pengaturan->update([
            'metode_absensi_siswa' => 'Hybrid',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => false,
            'izinkan_absen_tanpa_jadwal' => true,
        ]);

        $ta = TahunAjaran::factory()->aktif()->create();

        // 1. Siswa (without manual lokasi_pengguna assignment and without Jadwal)
        $siswaUser = User::factory()->siswa()->create(['is_active' => true]);
        $siswa = Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'kelas' => 'XI-RPL-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'Hadir']);

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $siswa->id,
            'jadwal_id' => null,
            'lokasi_id' => $lokasi->id,
            'status' => 'Hadir',
        ]);

        // Siswa check-out
        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-out', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(AbsensiSiswa::where('siswa_id', $siswa->id)->first()->jam_keluar);

        // Siswa dashboard includes GPS widget in Hybrid/Zonasi mode
        $this->actingAs($siswaUser)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Absensi Kehadiran (GPS');

        // 2. Guru
        $guruUser = User::factory()->guru()->create(['is_active' => true]);
        $guru = Guru::factory()->create(['user_id' => $guruUser->id]);

        $this->actingAs($guruUser)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'foto' => 'data:image/jpeg;base64,' . base64_encode('test-image'),
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'Hadir']);

        $this->assertDatabaseHas('absensi_guru', [
            'guru_id' => $guru->id,
            'lokasi_id' => $lokasi->id,
            'status' => 'Hadir',
        ]);

        // 3. Staff Akademik
        $staffUser = User::factory()->create(['role' => 'staff_akademik', 'is_active' => true]);
        $staff = StaffAkademik::create([
            'pengguna_id' => $staffUser->id,
            'nip' => 'STF001',
            'nama_lengkap' => 'Staff Uji',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        $this->actingAs($staffUser)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'Hadir']);

        $this->assertDatabaseHas('absensi_staff', [
            'staff_akademik_id' => $staff->id,
            'lokasi_id' => $lokasi->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_gps_respects_rfid_only_role_lock_and_custom_location_and_free_radius_and_saturday()
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:10:00', 'Asia/Jakarta')); // Saturday

        $lokasi = Lokasi::factory()->create([
            'nama' => 'Kampus Utama',
            'latitude' => -6.3021000,
            'longitude' => 106.6522000,
            'radius' => 100,
        ]);

        $ta = TahunAjaran::factory()->aktif()->create();
        $siswaUser = User::factory()->siswa()->create(['is_active' => true]);
        $siswa = Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'kelas' => 'XI-TKJ-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $pengaturan = PengaturanSekolah::first();

        // Role locked to RFID -> rejected
        $pengaturan->update(['metode_absensi_siswa' => 'RFID']);
        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-in', ['latitude' => -6.3021, 'longitude' => 106.6522])
            ->assertStatus(403);

        // Enable Zonasi, but Saturday disabled -> rejected
        $pengaturan->update([
            'metode_absensi_siswa' => 'Zonasi',
            'wajib_foto_gps' => false,
            'absensi_hari_sabtu' => false,
        ]);
        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-in', ['latitude' => -6.3021, 'longitude' => 106.6522])
            ->assertStatus(400);

        // Enable Saturday, but mode_lokasi_gps = khusus_pengguna with no assigned location -> rejected
        $pengaturan->update([
            'absensi_hari_sabtu' => true,
            'mode_lokasi_gps' => 'khusus_pengguna',
            'validasi_radius_gps' => true,
        ]);
        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-in', ['latitude' => -6.3021, 'longitude' => 106.6522])
            ->assertStatus(403);

        // Disable radius validation (e.g. PKL) -> check-in far away succeeds
        $pengaturan->update([
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => false,
        ]);
        $this->actingAs($siswaUser)
            ->postJson('/api/gps/check-in', ['latitude' => -6.1000, 'longitude' => 106.9000])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $siswa->id,
            'lokasi_id' => $lokasi->id,
        ]);
    }
}
