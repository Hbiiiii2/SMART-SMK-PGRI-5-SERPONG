<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaEditFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_siswa_edit_form_populates_saved_tanggal_lahir_and_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $tahunAjaran = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        // Create student through the actual store endpoint
        $this->actingAs($admin)->post(route('admin.siswa.store'), [
            'nisn' => '0081234567',
            'nama_lengkap' => 'Budi Santoso',
            'kelas' => 'XI-RPL-1',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2008-05-14',
            'alamat' => 'Jl. Raya Serpong No. 12',
            'no_telepon' => '081234567890',
            'tahun_ajaran_id' => $tahunAjaran->id,
            'email' => 'budi.santoso@smkpgri5.sch.id',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('admin.siswa.index'));

        $siswa = Siswa::with('user')->where('nisn', '0081234567')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.siswa.edit', $siswa));

        $response->assertStatus(200)
            ->assertSee('name="tanggal_lahir" value="2008-05-14"', false)
            ->assertDontSee('2008-05-14 00:00:00', false)
            ->assertSee('name="email" value="budi.santoso@smkpgri5.sch.id"', false)
            ->assertSee('name="nisn" value="0081234567"', false)
            ->assertSee('name="nama_lengkap" value="Budi Santoso"', false)
            ->assertSee('name="kelas" value="XI-RPL-1"', false)
            ->assertSee('Jl. Raya Serpong No. 12', false);

        // Updating without altering tanggal_lahir keeps all values intact
        $this->actingAs($admin)->put(route('admin.siswa.update', $siswa), [
            'nisn' => '0081234567',
            'nama_lengkap' => 'Budi Santoso Updated',
            'kelas' => 'XI-RPL-1',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2008-05-14',
            'alamat' => 'Jl. Raya Serpong No. 12',
            'no_telepon' => '081234567890',
            'tahun_ajaran_id' => $tahunAjaran->id,
            'email' => 'budi.updated@smkpgri5.sch.id',
        ])->assertRedirect(route('admin.siswa.index'));

        $siswa->refresh();
        $siswa->load('user');

        $this->assertSame('2008-05-14', $siswa->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('budi.updated@smkpgri5.sch.id', $siswa->user?->email);
        $this->assertSame('Budi Santoso Updated', $siswa->nama_lengkap);
    }

    public function test_admin_guru_and_tahun_ajaran_edit_forms_also_populate_date_and_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $guruUser = User::factory()->create([
            'name' => 'Siti Aminah',
            'email' => 'siti.guru@smkpgri5.sch.id',
            'role' => 'guru',
            'is_active' => true,
        ]);

        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nip' => '198801022015032001',
            'nama_lengkap' => 'Siti Aminah',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '1988-01-02',
            'alamat' => 'Tangerang Selatan',
        ]);

        $this->actingAs($admin)->get(route('admin.guru.edit', $guru))
            ->assertStatus(200)
            ->assertSee('name="tanggal_lahir" value="1988-01-02"', false)
            ->assertDontSee('1988-01-02 00:00:00', false)
            ->assertSee('name="email" value="siti.guru@smkpgri5.sch.id"', false);

        $tahunAjaran = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.tahun-ajaran.edit', $tahunAjaran))
            ->assertStatus(200)
            ->assertSee('name="tanggal_mulai" value="2026-07-01"', false)
            ->assertSee('name="tanggal_selesai" value="2027-06-30"', false)
            ->assertDontSee('2026-07-01 00:00:00', false);
    }
}
