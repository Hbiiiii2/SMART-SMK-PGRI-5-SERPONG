<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('SMART');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_guru_redirected_to_guru_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        \App\Models\Guru::create([
            'user_id' => $user->id,
            'nip' => '1234567890',
            'nama_lengkap' => $user->name,
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->actingAs($user)->get('/guru/dashboard');
        $response->assertStatus(200);
    }

    public function test_role_middleware_blocks_unauthorized(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guru)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_security_headers_present(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_user_can_login_with_remember_me_checkbox(): void
    {
        $loginPage = $this->get('/login');
        $loginPage->assertStatus(200)
            ->assertSee('id="remember"', false)
            ->assertSee('x-model="checked"', false)
            ->assertSee('peer-checked:bg-brand-500', false);

        $user = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect('/siswa/dashboard');
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_dedicated_login_pages_for_akademik_elearning_and_ujian_have_distinct_headings_and_descriptions(): void
    {
        $this->get(route('landing'))
            ->assertStatus(200)
            ->assertSee('/login/akademik', false)
            ->assertSee('/login/elearning', false)
            ->assertSee('/login/ujian', false);

        $this->get('/login/akademik')
            ->assertStatus(200)
            ->assertSee('<title>Login Manajemen Akademik | SMART+</title>', false)
            ->assertSee('Manajemen Akademik &amp; Presensi', false)
            ->assertSee('Pusat kendali presensi RFID');

        $this->get('/login/elearning')
            ->assertStatus(200)
            ->assertSee('<title>Login E-Learning &amp; LMS | SMART+</title>', false)
            ->assertSee('Ruang Belajar &amp; E-Learning', false)
            ->assertSee('Akses materi pembelajaran digital');

        $this->get('/login/ujian')
            ->assertStatus(200)
            ->assertSee('<title>Login Portal Ujian (CBT) | SMART+</title>', false)
            ->assertSee('Portal Ujian Berbasis Komputer')
            ->assertSee('Pelaksanaan ujian sekolah, penilaian tengah semester');

        $this->get('http://elearning.smkpgri5serpong.sch.id/login')
            ->assertStatus(200)
            ->assertSee('<title>Login E-Learning &amp; LMS | SMART+</title>', false)
            ->assertSee('Ruang Belajar &amp; E-Learning', false);

        $this->get('http://elearning.smkpgri5serpong.sch.id/')
            ->assertRedirect(route('login.elearning', [], false) ?: '/login/elearning');
    }
}
