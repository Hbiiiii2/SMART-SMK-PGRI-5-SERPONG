<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\AbsensiStaff;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\StaffAkademik;
use App\Models\TahunAjaran;
use App\Models\Tugas;
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

    public function test_anti_mock_gps_soft_flag_allows_borrowing_phone_and_marks_suspect_while_strict_mode_blocks()
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:10:00', 'Asia/Jakarta'));

        $lokasi = Lokasi::factory()->create([
            'nama' => 'Kampus Utama',
            'latitude' => -6.3021000,
            'longitude' => 106.6522000,
            'radius' => 100,
        ]);

        $pengaturan = PengaturanSekolah::first();
        $pengaturan->update([
            'metode_absensi_siswa' => 'Zonasi',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => false,
            'izinkan_absen_tanpa_jadwal' => true,
            'mode_anti_mock_gps' => 'peringatan',
            'maks_akurasi_gps_meter' => 100,
            'mode_deteksi_perangkat_bersama' => 'tandai',
            'batas_akun_per_perangkat' => 2,
        ]);

        $ta = TahunAjaran::factory()->aktif()->create();
        $siswa1User = User::factory()->siswa()->create(['is_active' => true]);
        $siswa1 = Siswa::factory()->create([
            'user_id' => $siswa1User->id,
            'kelas' => 'X-RPL-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $siswa2User = User::factory()->siswa()->create(['is_active' => true]);
        $siswa2 = Siswa::factory()->create([
            'user_id' => $siswa2User->id,
            'kelas' => 'X-RPL-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $siswa3User = User::factory()->siswa()->create(['is_active' => true]);
        $siswa3 = Siswa::factory()->create([
            'user_id' => $siswa3User->id,
            'kelas' => 'X-RPL-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $sharedToken = 'device-hp-teman-abc123';

        // Siswa 1 normal GPS on device -> clean (not suspect)
        $this->actingAs($siswa1User)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 18.5,
                'device_token' => $sharedToken,
                'samples' => [
                    ['lat' => -6.3021001, 'lng' => 106.6522001, 'accuracy' => 19],
                    ['lat' => -6.3021005, 'lng' => 106.6522003, 'accuracy' => 18.5],
                    ['lat' => -6.3021000, 'lng' => 106.6522000, 'accuracy' => 18.5],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $abs1 = AbsensiSiswa::where('siswa_id', $siswa1->id)->first();
        $this->assertFalse($abs1->is_mock_suspect);
        $this->assertEquals(18.5, $abs1->gps_accuracy);

        // Siswa 2 borrows Siswa 1's phone + has mock accuracy 0.5m in default soft-flag mode -> ALLOWED & flagged
        $this->actingAs($siswa2User)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 0.5,
                'device_token' => $sharedToken,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $abs2 = AbsensiSiswa::where('siswa_id', $siswa2->id)->first();
        $this->assertTrue($abs2->is_mock_suspect);
        $this->assertStringContainsString('Perangkat bersama', $abs2->mock_gps_reason);
        $this->assertStringContainsString('Indikasi Mock GPS', $abs2->mock_gps_reason);

        // Switch to strict mode (ketat) -> mock accuracy 0.5m is BLOCKED
        $pengaturan->update([
            'mode_anti_mock_gps' => 'ketat',
        ]);

        $this->actingAs($siswa3User)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 0.5,
                'device_token' => 'other-device-xyz',
            ])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        // Poor accuracy (> 100m) is also blocked in ketat mode
        $this->actingAs($siswa3User)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 185,
                'device_token' => 'other-device-xyz',
            ])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        // Enable shared device block mode -> Siswa 3 with same sharedToken is BLOCKED
        $pengaturan->update([
            'mode_anti_mock_gps' => 'peringatan',
            'mode_deteksi_perangkat_bersama' => 'blokir',
            'batas_akun_per_perangkat' => 2,
        ]);

        $this->actingAs($siswa3User)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 15,
                'device_token' => $sharedToken,
            ])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('pengaturan_sekolah', [
            'mode_deteksi_perangkat_bersama' => 'blokir',
            'batas_akun_per_perangkat' => 2,
        ]);
        $this->assertDatabaseHas('lokasi', ['id' => $lokasi->id]);
    }

    public function test_gps_checkin_enforces_three_stage_cutoff_hadir_terlambat_and_locked_alfa()
    {
        $lokasi = Lokasi::factory()->create([
            'nama' => 'Kampus Utama',
            'latitude' => -6.3021000,
            'longitude' => 106.6522000,
            'radius' => 100,
        ]);

        $pengaturan = PengaturanSekolah::first();
        $pengaturan->update([
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
            'toleransi_terlambat_menit' => 15, // Hadir s/d 07:15
            'toleransi_alpha_menit' => 30,     // Terlambat 07:16 - 07:30, > 07:30 terkunci Alfa
            'metode_absensi_siswa' => 'Zonasi',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => false,
            'izinkan_absen_tanpa_jadwal' => true,
        ]);

        $ta = TahunAjaran::factory()->aktif()->create();

        // Stage 1: Check-in at 07:10 -> Hadir
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:10:00', 'Asia/Jakarta'));
        $u1 = User::factory()->siswa()->create(['is_active' => true]);
        $s1 = Siswa::factory()->create(['user_id' => $u1->id, 'kelas' => 'X-RPL-1', 'tahun_ajaran_id' => $ta->id]);
        $this->actingAs($u1)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 15,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'Hadir']);
        $this->assertDatabaseHas('absensi_siswa', ['siswa_id' => $s1->id, 'status' => 'Hadir']);

        // Stage 2: Check-in at 07:25 (between 07:16 and 07:30) -> Terlambat
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:25:00', 'Asia/Jakarta'));
        $u2 = User::factory()->siswa()->create(['is_active' => true]);
        $s2 = Siswa::factory()->create(['user_id' => $u2->id, 'kelas' => 'X-RPL-1', 'tahun_ajaran_id' => $ta->id]);
        $this->actingAs($u2)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 15,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'Terlambat']);
        $this->assertDatabaseHas('absensi_siswa', ['siswa_id' => $s2->id, 'status' => 'Terlambat']);

        // Stage 3: Check-in at 07:35 (> 07:30) -> Blocked (400) & auto-recorded Alfa
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:35:00', 'Asia/Jakarta'));
        $u3 = User::factory()->siswa()->create(['is_active' => true]);
        $s3 = Siswa::factory()->create(['user_id' => $u3->id, 'kelas' => 'X-RPL-1', 'tahun_ajaran_id' => $ta->id]);
        $this->actingAs($u3)
            ->postJson('/api/gps/check-in', [
                'latitude' => -6.3021000,
                'longitude' => 106.6522000,
                'accuracy' => 15,
            ])
            ->assertStatus(400)
            ->assertJson(['success' => false, 'status' => 'Alfa']);
        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $s3->id,
            'status' => 'Alfa',
            'jam_masuk' => null,
        ]);
        $this->assertDatabaseHas('lokasi', ['id' => $lokasi->id]);
    }

    public function test_siswa_ui_views_render_cleanly_on_mobile_and_desktop_without_gradient_slop()
    {
        $ta = TahunAjaran::factory()->aktif()->create();
        $guru = Guru::factory()->create(['nama_lengkap' => 'Drs. Hendra Wijaya']);
        $mapel = MataPelajaran::factory()->create(['nama' => 'Pemrograman Web']);
        $lokasi = Lokasi::factory()->create(['nama' => 'Lab RPL 1']);

        $siswaUser = User::factory()->siswa()->create(['is_active' => true]);
        Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'nama_lengkap' => 'Rafi Akbar Pratama',
            'kelas' => 'XI-RPL-2',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $jadwal = Jadwal::factory()->create([
            'kelas' => 'XI-RPL-2',
            'hari' => 'Senin',
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'lokasi_id' => $lokasi->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $materi = Materi::create([
            'guru_id' => $guru->id,
            'kelas' => 'XI-RPL-2',
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Pengenalan Laravel Routing',
            'deskripsi' => 'Pelajari routing dasar dan controller.',
            'tipe_materi' => 'lainnya',
            'file_path_atau_url' => 'https://laravel.com/docs',
        ]);

        $tugas = Tugas::create([
            'guru_id' => $guru->id,
            'kelas' => 'XI-RPL-2',
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Latihan Membuat Route Resource',
            'deskripsi' => 'Buat 3 route GET dan POST.',
            'tenggat_waktu' => Carbon::now('Asia/Jakarta')->addDays(3),
            'tipe_pengumpulan' => 'link_url',
        ]);

        $this->actingAs($siswaUser)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Rafi Akbar Pratama')
            ->assertSee('Navigasi cepat mobile')
            ->assertSee('Pemrograman Web')
            ->assertDontSee('from-brand-500 to-indigo-600')
            ->assertDontSee('Yey!');

        $this->actingAs($siswaUser)
            ->get(route('siswa.jadwal.show', $jadwal))
            ->assertOk()
            ->assertSee('Pemrograman Web')
            ->assertSee('Drs. Hendra Wijaya')
            ->assertSee('Pengenalan Laravel Routing')
            ->assertDontSee('from-blue-500 to-brand-500');

        $this->actingAs($siswaUser)
            ->get(route('siswa.materi.show', $materi))
            ->assertOk()
            ->assertSee('Pengenalan Laravel Routing')
            ->assertSee('Buka Tautan Materi')
            ->assertDontSee('from-teal-400 to-emerald-500');

        $this->actingAs($siswaUser)
            ->get(route('siswa.tugas.show', $tugas))
            ->assertOk()
            ->assertSee('Latihan Membuat Route Resource')
            ->assertSee('Status &amp; Pengumpulan', false)
            ->assertDontSee('from-indigo-500 to-purple-600');
    }
}
