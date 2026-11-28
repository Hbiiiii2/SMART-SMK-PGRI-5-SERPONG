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

    public function test_guru_dashboard_displays_lms_class_cards_and_classroom_manager(): void
    {
        $tahun = \App\Models\TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Ruang XI BD',
            'latitude' => -6.34,
            'longitude' => 106.67,
            'radius' => 100,
        ]);

        $mapel = \App\Models\MataPelajaran::create([
            'kode' => 'BD-B2',
            'nama' => 'Marketing',
            'kelompok' => 'konsentrasi',
        ]);

        $user = User::factory()->create(['role' => 'guru']);
        $guru = Guru::factory()->create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Anisa Fitri, S.M',
        ]);

        $otherUser = User::factory()->create(['role' => 'guru']);
        Guru::factory()->create(['user_id' => $otherUser->id]);

        \App\Models\Siswa::create([
            'nisn' => '0099112233',
            'nama_lengkap' => 'Siswa BD Satu',
            'jenis_kelamin' => 'P',
            'kelas' => 'XI BD',
            'tahun_ajaran_id' => $tahun->id,
            'status_siswa' => 'aktif',
        ]);

        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $tahun->id,
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '13:00',
            'jam_selesai' => '14:30',
        ]);
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $tahun->id,
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Rabu',
            'jam_mulai' => '15:00',
            'jam_selesai' => '16:00',
        ]);

        \App\Models\Materi::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'kelas' => 'XI BD',
            'judul' => 'Modul Digital Marketing',
            'tipe_materi' => 'pdf',
            'file_path_atau_url' => 'materi/test.pdf',
        ]);

        \App\Models\Tugas::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'kelas' => 'XI BD',
            'judul' => 'Analisis Target Pasar',
            'deskripsi' => 'Buat analisis segmentasi pasar.',
            'tenggat_waktu' => now()->addDays(3),
            'tipe_pengumpulan' => 'file',
        ]);

        // 1. Dashboard shows grouped card (2 jadwals merged into 1 card for XI BD - Marketing)
        $dashRes = $this->actingAs($user)->get(route('guru.dashboard'));
        $dashRes->assertStatus(200)
            ->assertSee('Kelas Mengajar &amp; LMS Guru', false)
            ->assertSee('1 Kelas Mapel')
            ->assertSee('Marketing')
            ->assertSee('Kelas: XI BD')
            ->assertSee('BD-B2')
            ->assertSee('1 Materi')
            ->assertSee('1 Tugas')
            ->assertSee('Buka Kelas');

        // 2. Classroom detail page shows scoped Materi & Tugas for assigned teacher
        $kelasRes = $this->actingAs($user)->get(route('guru.kelas.show', [
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
        ]));
        $kelasRes->assertStatus(200)
            ->assertSee('Modul Digital Marketing')
            ->assertSee('Analisis Target Pasar')
            ->assertSee('Tambah Materi')
            ->assertSee('Tambah Tugas');

        // 3. Another teacher cannot access this classroom
        $this->actingAs($otherUser)->get(route('guru.kelas.show', [
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
        ]))->assertStatus(403);

        // 4. Materi & Tugas index filter + prefilled create form
        $this->actingAs($user)->get(route('guru.materi.index', [
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
        ]))->assertStatus(200)
            ->assertSee('Modul Digital Marketing');

        $this->actingAs($user)->get(route('guru.tugas.create', [
            'kelas' => 'XI BD',
            'mata_pelajaran_id' => $mapel->id,
            'redirect_to' => 'kelas',
        ]))->assertStatus(200)
            ->assertSee('value="kelas"', false);
    }
}
