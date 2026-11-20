<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Lokasi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        Cache::flush();

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
                ->assertSee('Monitoring Kehadiran Siswa Hari Ini')
                ->assertSee('Rekap Kehadiran Siswa Per Kelas')
                ->assertSee('Detail Kehadiran Siswa Per Kelas')
                ->assertSee('KPI Target Kehadiran Guru')
                ->assertSee('Peta Sebaran Lokasi Absensi GPS')
                ->assertSee('#16a34a', false)
                ->assertSee('#2563eb', false);
        }

        $ks = User::factory()->create([
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($ks)
            ->get(route('ks.dashboard'))
            ->assertStatus(200)
            ->assertSee('Guru Hadir')
            ->assertSee('Monitoring Kehadiran Siswa Hari Ini')
            ->assertSee('Rekap Kehadiran Siswa Per Kelas')
            ->assertSee('Detail Kehadiran Siswa Per Kelas')
            ->assertSee('KPI Target Kehadiran Guru')
            ->assertSee('Peta Sebaran Lokasi Absensi GPS')
            ->assertSee('#16a34a', false)
            ->assertSee('#2563eb', false);
    }

    public function test_dashboard_displays_all_students_and_dual_color_gps_coordinates_for_guru_and_siswa()
    {
        Cache::flush();
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $lokasi = Lokasi::create([
            'nama' => 'Kampus Utama Serpong',
            'deskripsi' => 'Area Sekolah',
            'latitude' => -6.30210,
            'longitude' => 106.65220,
            'radius' => 150,
        ]);

        $guruUser = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nip' => '19880101001',
            'nama_lengkap' => 'Budi Santoso Guru',
            'jenis_kelamin' => 'L',
        ]);

        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => $today,
            'jam_masuk' => '07:05:00',
            'status' => 'Hadir',
            'latitude' => -6.30215,
            'longitude' => 106.65225,
        ]);

        $ta = TahunAjaran::factory()->aktif()->create();

        $siswaHadir = Siswa::factory()->create([
            'nama_lengkap' => 'Andi Pratama Siswa',
            'kelas' => 'X RPL 1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        AbsensiSiswa::create([
            'siswa_id' => $siswaHadir->id,
            'lokasi_id' => $lokasi->id,
            'tanggal' => $today,
            'jam_masuk' => '07:10:00',
            'status' => 'Hadir',
            'latitude' => -6.30240,
            'longitude' => 106.65250,
        ]);

        $siswaBelumAbsen = Siswa::factory()->create([
            'nama_lengkap' => 'Citra Dewi Belum Absen',
            'kelas' => 'XI TKJ 2',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Budi Santoso Guru')
            ->assertSee('-6.30215')
            ->assertSee('Andi Pratama Siswa')
            ->assertSee('-6.30240')
            ->assertSee('Citra Dewi Belum Absen')
            ->assertSee('X RPL 1')
            ->assertSee('XI TKJ 2')
            ->assertSee('Kampus Utama Serpong')
            ->assertSee('#16a34a', false)
            ->assertSee('#2563eb', false);

        $this->assertDatabaseHas('siswa', ['id' => $siswaBelumAbsen->id]);
    }
}
