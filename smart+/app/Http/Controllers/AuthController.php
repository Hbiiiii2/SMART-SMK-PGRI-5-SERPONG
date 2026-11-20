<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        $host = strtolower($request->getHost());
        $queryPortal = strtolower((string) $request->query('portal', ''));

        if (str_contains($host, 'elearning') || $queryPortal === 'elearning') {
            return view('pages.auth.login-elearning', [
                'title' => 'Portal E-Learning & LMS',
            ]);
        }

        if (str_contains($host, 'cbt') || str_contains($host, 'ujian') || in_array($queryPortal, ['cbt', 'ujian'], true)) {
            return redirect()->away(rtrim((string) config('services.cbt.public_url', 'https://cbt.smkpgri5serpong.sch.id'), '/') . '/login');
        }

        return view('pages.auth.login-akademik', [
            'title' => 'Portal Admin Akademik',
        ]);
    }

    public function showLoginAkademik()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('pages.auth.login-akademik', [
            'title' => 'Portal Admin Akademik',
        ]);
    }

    public function showLoginElearning()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('pages.auth.login-elearning', [
            'title' => 'Portal E-Learning & LMS',
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan gagal. Silakan coba lagi dalam ' . $seconds . ' detik.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60 * 5);

            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        $portalInput = strtolower((string) $request->input('portal', ''));
        if (in_array($portalInput, ['akademik', 'elearning'], true)) {
            $request->session()->put('auth_portal', $portalInput);
        }

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'login',
            'deskripsi' => $user->name . ' berhasil masuk',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->redirectByRole();
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $host = strtolower($request->getHost());
        $authPortal = $request->session()->get('auth_portal');

        if ($user) {
            LogAktivitas::create([
                'user_id' => $user->id,
                'aksi' => 'logout',
                'deskripsi' => $user->name . ' telah keluar',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (!str_contains($host, 'elearning') && !str_contains($host, 'akademik') && $authPortal === 'elearning') {
            return redirect()->route('login.elearning');
        }

        return redirect()->route('login');
    }

    public function profile()
    {
        $user = Auth::user();

        return view('pages.auth.profile', [
            'title' => 'Profil Saya',
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'update_profile',
            'deskripsi' => 'Profil berhasil diperbarui',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function changePassword()
    {
        return view('pages.auth.change-password', ['title' => 'Ubah Password']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        LogAktivitas::create([
            'user_id' => $user->id,
            'aksi' => 'change_password',
            'deskripsi' => 'Password berhasil diubah',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    private function redirectByRole()
    {
        $user = Auth::user();

        return match ($user->role) {
            'super_admin', 'admin_sekolah' => redirect()->route('admin.dashboard'),
            'kepala_sekolah' => redirect()->route('ks.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'direktur' => redirect()->route('direktur.dashboard'),
            'staff_akademik' => redirect()->route('staff.dashboard'),
            'siswa' => redirect()->route('siswa.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
