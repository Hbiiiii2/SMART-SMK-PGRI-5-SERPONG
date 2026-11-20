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
        return $this->renderLoginPortal($request);
    }

    public function showLoginAkademik(Request $request)
    {
        return $this->renderLoginPortal($request, 'akademik');
    }

    public function showLoginElearning(Request $request)
    {
        return $this->renderLoginPortal($request, 'elearning');
    }

    public function showLoginUjian(Request $request)
    {
        return $this->renderLoginPortal($request, 'ujian');
    }

    private function renderLoginPortal(Request $request, ?string $forcedPortal = null)
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        $portalContext = $this->resolvePortalContext($request, $forcedPortal);

        return view('pages.auth.signin', [
            'title' => $portalContext['title'],
            'portalContext' => $portalContext,
        ]);
    }

    private function resolvePortalContext(Request $request, ?string $forcedPortal = null): array
    {
        $key = $forcedPortal;

        if (!$key) {
            $queryPortal = strtolower((string) $request->query('portal', ''));
            if ($queryPortal === 'cbt') {
                $queryPortal = 'ujian';
            }
            if (in_array($queryPortal, ['akademik', 'elearning', 'ujian'], true)) {
                $key = $queryPortal;
            }
        }

        if (!$key) {
            $host = strtolower($request->getHost());
            if (str_contains($host, 'elearning')) {
                $key = 'elearning';
            } elseif (str_contains($host, 'cbt') || str_contains($host, 'ujian')) {
                $key = 'ujian';
            } else {
                $key = 'akademik';
            }
        }

        $portals = [
            'akademik' => [
                'key' => 'akademik',
                'title' => 'Login Manajemen Akademik',
                'badge' => 'Portal Manajemen Akademik & Presensi',
                'heading' => 'Manajemen Akademik & Presensi',
                'description' => 'Pusat kendali presensi RFID & GPS Geofencing, penjadwalan akademik, pengelolaan E-Rapor Kurikulum Merdeka, serta pemantauan kehadiran guru, staf, dan siswa secara real-time.',
                'email_label' => 'Email Akun Akademik',
                'email_placeholder' => 'Masukkan email guru, staf, atau admin',
                'button_text' => 'Masuk ke Portal Akademik',
                'audience' => 'Admin Sekolah, Kepala Sekolah, Guru & Staf Akademik',
                'highlights' => ['Presensi RFID & GPS', 'E-Rapor Terintegrasi', 'Monitoring Kehadiran'],
            ],
            'elearning' => [
                'key' => 'elearning',
                'title' => 'Login E-Learning & LMS',
                'badge' => 'Portal E-Learning & Ruang Belajar Digital',
                'heading' => 'Ruang Belajar & E-Learning',
                'description' => 'Akses materi pembelajaran digital (modul PDF & video interaktif), pengumpulan tugas kelas, jadwal mata pelajaran harian, serta absensi kehadiran belajar siswa dan guru.',
                'email_label' => 'Email Siswa / Guru',
                'email_placeholder' => 'Masukkan email akun belajar Anda',
                'button_text' => 'Masuk ke Ruang E-Learning',
                'audience' => 'Siswa & Guru Pengampu Mata Pelajaran',
                'highlights' => ['Materi & Video Ajar', 'Pengumpulan Tugas', 'Jadwal & Absensi Kelas'],
            ],
            'ujian' => [
                'key' => 'ujian',
                'title' => 'Login Portal Ujian (CBT)',
                'badge' => 'Computer Based Test & Evaluasi Akademik',
                'heading' => 'Portal Ujian Berbasis Komputer',
                'description' => 'Pelaksanaan ujian sekolah, penilaian tengah semester (STS), penilaian akhir semester (SAS), dan evaluasi kompetensi berbasis komputer dengan pengawasan token ujian real-time.',
                'email_label' => 'Email Peserta / Pengawas Ujian',
                'email_placeholder' => 'Masukkan email akun peserta atau guru CBT',
                'button_text' => 'Masuk ke Portal Ujian',
                'audience' => 'Peserta Ujian (Siswa), Guru Pembuat Soal & Pengawas CBT',
                'highlights' => ['Ujian STS & SAS', 'Validasi Token Ujian', 'Evaluasi Terintegrasi'],
            ],
        ];

        return $portals[$key] ?? $portals['akademik'];
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
