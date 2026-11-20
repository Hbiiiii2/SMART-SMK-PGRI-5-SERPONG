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
}
