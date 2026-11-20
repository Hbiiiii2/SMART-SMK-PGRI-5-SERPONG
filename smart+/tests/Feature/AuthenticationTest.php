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
            ->assertSee('peer-checked:bg-[#1b61c9]', false);

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

    public function test_akademik_and_elearning_login_pages_are_completely_separate_without_portal_switcher(): void
    {
        $this->get(route('landing'))
            ->assertStatus(200)
            ->assertSee('/login/akademik', false)
            ->assertSee('/login/elearning', false)
            ->assertSee('https://cbt.smkpgri5serpong.sch.id/login', false);

        // Akademik: khusus admin akademik, pakai Airtable blue tokens, tanpa quick switcher
        $this->get('/login/akademik')
            ->assertStatus(200)
            ->assertSee('Portal Admin Akademik | SMART+')
            ->assertSee('Masuk Manajemen Akademik')
            ->assertSee('#1b61c9', false)
            ->assertDontSee('Pilih portal login')
            ->assertDontSee('/login/elearning', false)
            ->assertDontSee('/login/ujian', false);

        // Subdomain akademik otomatis menampilkan halaman login Akademik
        $this->get('http://akademik.smkpgri5serpong.sch.id/')
            ->assertRedirect(route('login', [], false) ?: '/login');

        $this->get('http://akademik.smkpgri5serpong.sch.id/login')
            ->assertStatus(200)
            ->assertSee('Masuk Manajemen Akademik')
            ->assertDontSee('Masuk ke Portal E-Learning');

        // E-Learning: khusus LMS Siswa & Guru, tanpa quick switcher
        $this->get('/login/elearning')
            ->assertStatus(200)
            ->assertSee('Portal E-Learning &amp; LMS | SMART+', false)
            ->assertSee('Masuk ke Portal E-Learning')
            ->assertSee('Workspace Siswa')
            ->assertSee('Workspace Guru Pengampu')
            ->assertDontSee('Pilih portal login')
            ->assertDontSee('/login/akademik', false)
            ->assertDontSee('/login/ujian', false);

        // Subdomain elearning otomatis menampilkan halaman login E-Learning
        $this->get('http://elearning.smkpgri5serpong.sch.id/')
            ->assertRedirect(route('login', [], false) ?: '/login');

        $this->get('http://elearning.smkpgri5serpong.sch.id/login')
            ->assertStatus(200)
            ->assertSee('Masuk ke Portal E-Learning')
            ->assertDontSee('Masuk Manajemen Akademik');

        // Portal Ujian (CBT) terpisah keluar ke aplikasi CBT tersendiri
        $this->get('/login/ujian')
            ->assertRedirect('https://cbt.smkpgri5serpong.sch.id/login');

        // Logout di subdomain elearning tetap kembali ke /login (subdomain elearning)
        $guruUser = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->post('http://elearning.smkpgri5serpong.sch.id/login', [
            'email' => $guruUser->email,
            'password' => 'password',
            'portal' => 'elearning',
        ])->assertRedirect('http://elearning.smkpgri5serpong.sch.id/guru/dashboard');

        $this->post('http://elearning.smkpgri5serpong.sch.id/logout')
            ->assertRedirect('http://elearning.smkpgri5serpong.sch.id/login');

        // Login via path /login/elearning di host utama lalu logout kembali ke /login/elearning
        $this->post('http://smart.smkpgri5serpong.sch.id/login', [
            'email' => $guruUser->email,
            'password' => 'password',
            'portal' => 'elearning',
        ])->assertRedirect('http://smart.smkpgri5serpong.sch.id/guru/dashboard');

        $this->post('http://smart.smkpgri5serpong.sch.id/logout')
            ->assertRedirect('http://smart.smkpgri5serpong.sch.id/login/elearning');
    }
}
