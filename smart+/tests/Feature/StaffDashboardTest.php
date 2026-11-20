<?php

namespace Tests\Feature;

use App\Models\PengaturanSekolah;
use App\Models\StaffAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
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
            'metode_absensi_staff' => 'Hybrid',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => true,
        ]);
    }

    public function test_staff_dashboard_hybrid_shows_both_rfid_and_gps()
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        StaffAkademik::create([
            'pengguna_id' => $user->id,
            'nip' => '123456789',
            'nama_lengkap' => 'Staff Test',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Hybrid (RFID Kiosk + GPS Lokasi)');
        $response->assertSee('Absensi Kehadiran (GPS');
    }

    public function test_staff_dashboard_rfid_hides_gps_view()
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        StaffAkademik::create([
            'pengguna_id' => $user->id,
            'nip' => '123456791',
            'nama_lengkap' => 'Staff RFID',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        PengaturanSekolah::first()->update(['metode_absensi_staff' => 'RFID']);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('RFID Kiosk');
        $response->assertDontSee('navigator.geolocation.getCurrentPosition');
    }

    public function test_staff_dashboard_zonasi_shows_zonasi_view()
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        StaffAkademik::create([
            'pengguna_id' => $user->id,
            'nip' => '123456790',
            'nama_lengkap' => 'Staff Test 2',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        PengaturanSekolah::first()->update(['metode_absensi_staff' => 'Zonasi']);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Absensi Kehadiran (GPS');
        $response->assertDontSee('Metode absensi Anda saat ini menggunakan');
    }
}
