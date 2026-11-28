<?php

namespace Tests\Feature;

use App\Models\ClassHomeroom;
use App\Models\Guru;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RombelCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guruUser;
    private Guru $guru;
    private TahunAjaran $tahunAjaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $this->guruUser = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->guru = Guru::create([
            'user_id' => $this->guruUser->id,
            'nip' => '198505052010011001',
            'nama_lengkap' => 'Pak Budi Wali Kelas',
            'jenis_kelamin' => 'L',
        ]);

        $this->tahunAjaran = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);
    }

    public function test_admin_can_create_edit_and_cascade_rename_rombel(): void
    {
        $this->actingAs($this->admin)->get(route('admin.kelas.index'))
            ->assertStatus(200);

        $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'nama' => 'X AK',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'wali_kelas_guru_id' => $this->guru->id,
            'kapasitas' => 36,
            'is_aktif' => 1,
        ])->assertRedirect(route('admin.kelas.index'));

        $rombel = Rombel::where('nama', 'X AK')->firstOrFail();
        $this->assertSame('X', $rombel->tingkat);
        $this->assertSame('Akuntansi dan Keuangan Lembaga', $rombel->kompetensi_keahlian);

        // Homeroom synced automatically
        $this->assertDatabaseHas('class_homerooms', [
            'kelas' => 'X AK',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'guru_id' => $this->guru->id,
        ]);

        // Attach a student to this rombel
        $siswa = Siswa::factory()->create([
            'kelas' => $rombel->nama,
            'rombel_id' => $rombel->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);

        // Deleting rombel with students must be blocked
        $this->actingAs($this->admin)->delete(route('admin.kelas.destroy', $rombel))
            ->assertRedirect(route('admin.kelas.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('rombels', ['id' => $rombel->id]);

        // Rename rombel cascades to siswa & class_homerooms
        $this->actingAs($this->admin)->put(route('admin.kelas.update', $rombel), [
            'nama' => 'X AK 1',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'tingkat' => 'X',
            'kompetensi_keahlian' => 'Akuntansi dan Keuangan Lembaga',
            'wali_kelas_guru_id' => $this->guru->id,
            'is_aktif' => 1,
        ])->assertRedirect(route('admin.kelas.index'));

        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->id,
            'kelas' => 'X AK 1',
            'rombel_id' => $rombel->id,
        ]);
        $this->assertDatabaseHas('class_homerooms', [
            'kelas' => 'X AK 1',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }

    public function test_admin_can_delete_empty_rombel_and_guru_is_forbidden(): void
    {
        $rombel = Rombel::create([
            'nama' => 'XII MP 2',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'tingkat' => 'XII',
            'is_aktif' => true,
        ]);

        $this->actingAs($this->guruUser)->get(route('admin.kelas.index'))
            ->assertStatus(403);

        $this->actingAs($this->admin)->delete(route('admin.kelas.destroy', $rombel))
            ->assertRedirect(route('admin.kelas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('rombels', ['id' => $rombel->id]);
    }
}
