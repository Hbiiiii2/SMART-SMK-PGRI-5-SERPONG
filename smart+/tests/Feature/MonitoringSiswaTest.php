<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringSiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_siswa_page_loads()
    {
        $user = User::factory()->create([
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/kepala-sekolah/monitoring-siswa');

        $response->assertStatus(200);
    }

    public function test_admin_and_super_admin_dashboard_shows_monitoring_and_kpi_like_kepala_sekolah()
    {
        foreach (['super_admin', 'admin_sekolah'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('admin.dashboard'))
                ->assertStatus(200)
                ->assertSee('Guru Hadir')
                ->assertSee('Siswa Hadir')
                ->assertSee('Guru Sakit/Izin')
                ->assertSee('Siswa Sakit/Izin')
                ->assertSee('Monitoring Kehadiran Guru')
                ->assertSee('KPI Target Kehadiran Guru');
        }

        $ks = User::factory()->create([
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($ks)
            ->get(route('ks.dashboard'))
            ->assertStatus(200)
            ->assertSee('Guru Hadir')
            ->assertSee('KPI Target Kehadiran Guru');
    }
}
