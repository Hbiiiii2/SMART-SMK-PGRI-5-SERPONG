<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\PengaturanSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruDashboardTest extends TestCase
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
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'RFID',
            'mode_lokasi_gps' => 'semua_lokasi',
            'validasi_radius_gps' => true,
            'wajib_foto_gps' => true,
        ]);
    }

    public function test_guru_dashboard_hybrid_shows_gps_and_checkin_button()
    {
        $user = User::factory()->create(['role' => 'guru']);
        Guru::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Check-in Sekarang');
        $response->assertSee('Absensi Kehadiran (GPS');
    }

    public function test_guru_dashboard_rfid_shows_status_link_not_gps()
    {
        $user = User::factory()->create(['role' => 'guru']);
        Guru::factory()->create(['user_id' => $user->id]);

        PengaturanSekolah::first()->update(['metode_absensi_guru' => 'RFID']);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Lihat Status Absensi');
        $response->assertDontSee('navigator.geolocation.getCurrentPosition');
    }

    public function test_guru_dashboard_zonasi_shows_zonasi_view()
    {
        $user = User::factory()->create(['role' => 'guru']);
        Guru::factory()->create(['user_id' => $user->id]);

        PengaturanSekolah::first()->update(['metode_absensi_guru' => 'Zonasi']);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Check-in Sekarang');
        $response->assertSee('Absensi Kehadiran (GPS');
    }
}
