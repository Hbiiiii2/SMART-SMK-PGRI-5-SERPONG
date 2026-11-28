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

    public function test_admin_siswa_edit_form_populates_saved_tanggal_lahir_in_dd_mm_yyyy_and_email(): void
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

        $rombel = \App\Models\Rombel::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => 'Ganjil',
            'nama' => 'XI-RPL-1',
            'tingkat' => 'XI',
            'is_aktif' => true,
        ]);

        // Submit tanggal lahir dalam format DD-MM-YYYY saat tambah siswa
        $this->actingAs($admin)->post(route('admin.siswa.store'), [
            'nisn' => '0081234567',
            'nama_lengkap' => 'Budi Santoso',
            'rombel_id' => $rombel->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '14-05-2008',
            'alamat' => 'Jl. Raya Serpong No. 12',
            'no_telepon' => '081234567890',
            'tahun_ajaran_id' => $tahunAjaran->id,
            'email' => 'budi.santoso@smkpgri5.sch.id',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('admin.siswa.index'));

        $siswa = Siswa::with('user')->where('nisn', '0081234567')->firstOrFail();
        $this->assertSame('2008-05-14', $siswa->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('XI-RPL-1', $siswa->kelas);
        $this->assertSame($rombel->id, $siswa->rombel_id);

        $response = $this->actingAs($admin)->get(route('admin.siswa.edit', $siswa));

        $response->assertStatus(200)
            ->assertSee('value="14-05-2008"', false)
            ->assertSee('placeholder="DD-MM-YYYY"', false)
            ->assertDontSee('2008-05-14 00:00:00', false)
            ->assertSee('name="email" value="budi.santoso@smkpgri5.sch.id"', false)
            ->assertSee('name="nisn" value="0081234567"', false)
            ->assertSee('name="nama_lengkap" value="Budi Santoso"', false)
            ->assertSee('name="rombel_id"', false)
            ->assertSee('XI-RPL-1', false)
            ->assertSee('Jl. Raya Serpong No. 12', false);

        // Uji tanggal ambigu (04-07-2008 = 4 Juli 2008, bukan 7 April) tetap akurat
        $this->actingAs($admin)->put(route('admin.siswa.update', $siswa), [
            'nisn' => '0081234567',
            'nama_lengkap' => 'Budi Santoso Updated',
            'rombel_id' => $rombel->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '04-07-2008',
            'alamat' => 'Jl. Raya Serpong No. 12',
            'no_telepon' => '081234567890',
            'tahun_ajaran_id' => $tahunAjaran->id,
            'email' => 'budi.updated@smkpgri5.sch.id',
        ])->assertRedirect(route('admin.siswa.index'));

        $siswa->refresh();
        $siswa->load('user');

        $this->assertSame('2008-07-04', $siswa->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('budi.updated@smkpgri5.sch.id', $siswa->user?->email);

        $this->actingAs($admin)->get(route('admin.siswa.edit', $siswa))
            ->assertStatus(200)
            ->assertSee('value="04-07-2008"', false);
    }

    public function test_invalid_date_fails_validation_and_valid_old_date_remains_dd_mm_yyyy_on_other_errors(): void
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

        $this->actingAs($admin)->from(route('admin.siswa.create'))
            ->post(route('admin.siswa.store'), [
                'nisn' => '0089999999',
                'nama_lengkap' => 'Test Invalid Date',
                'kelas' => 'X-1',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '31-02-2008',
                'tahun_ajaran_id' => $tahunAjaran->id,
                'email' => 'invaliddate@smkpgri5.sch.id',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertSessionHasErrors('tanggal_lahir');

        $response = $this->actingAs($admin)->followingRedirects()
            ->from(route('admin.siswa.create'))
            ->post(route('admin.siswa.store'), [
                'nisn' => '0089999998',
                'nama_lengkap' => 'Test Old Date Format',
                'kelas' => 'X-1',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '04-07-2008',
                'tahun_ajaran_id' => $tahunAjaran->id,
                'email' => 'bukan-email-valid',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertSee('value="04-07-2008"', false);
    }

    public function test_admin_guru_and_tahun_ajaran_edit_forms_also_populate_dd_mm_yyyy_and_email(): void
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
            ->assertSee('value="02-01-1988"', false)
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
            ->assertSee('value="01-07-2026"', false)
            ->assertSee('value="30-06-2027"', false)
            ->assertDontSee('2026-07-01 00:00:00', false);
    }
}
