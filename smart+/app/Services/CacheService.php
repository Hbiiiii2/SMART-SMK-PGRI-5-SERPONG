<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const SHORT = 300;
    private const MEDIUM = 900;
    private const LONG = 3600;

    public static function guruList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_guru', self::LONG, function () {
            return Guru::with('user')->get();
        });
    }

    public static function siswaList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_siswa', self::MEDIUM, function () {
            return Siswa::with('user')->get();
        });
    }

    public static function tahunAjaranList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_tahun_ajaran', self::LONG, function () {
            return TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        });
    }

    public static function tahunAjaranAktif(): ?TahunAjaran
    {
        return Cache::remember('tahun_ajaran_aktif', self::LONG, function () {
            return TahunAjaran::where('is_aktif', true)->first();
        });
    }

    public static function mataPelajaranList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_mapel', self::LONG, function () {
            return MataPelajaran::all();
        });
    }

    public static function lokasiList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_lokasi', self::LONG, function () {
            return Lokasi::all();
        });
    }

    public static function guruCount(): int
    {
        return Cache::remember('count_guru', self::SHORT, fn () => Guru::count());
    }

    public static function siswaCount(): int
    {
        return Cache::remember('count_siswa', self::SHORT, fn () => Siswa::count());
    }

    public static function rfidActiveCount(): int
    {
        return Cache::remember('count_rfid_active', self::SHORT, fn () => \App\Models\RfidCard::where('is_active', true)->count());
    }

    public static function flushDropdowns(): void
    {
        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

        Cache::forget('dropdown_guru');
        Cache::forget('dropdown_siswa');
        Cache::forget('dropdown_tahun_ajaran');
        Cache::forget('dropdown_mapel');
        Cache::forget('dropdown_lokasi');
        Cache::forget('tahun_ajaran_aktif');
        Cache::forget('count_guru');
        Cache::forget('count_siswa');
        Cache::forget('count_rfid_active');
        Cache::forget('admin_dashboard');
        Cache::forget("admin_dashboard_{$today}");
        Cache::forget("ks_dashboard_{$today}");
        Cache::forget('pengaturan_sekolah');
    }

    public static function dashboardAdmin(string $today): array
    {
        return Cache::remember('admin_dashboard_' . $today, self::SHORT, function () use ($today) {
            return [
                'guru_count' => self::guruCount(),
                'siswa_count' => self::siswaCount(),
                'rfid_count' => self::rfidActiveCount(),
                'tahun_ajaran_aktif' => self::tahunAjaranAktif()->nama ?? '-',
                'guru_hadir_hari_ini' => \App\Models\AbsensiGuru::whereDate('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'siswa_hadir_hari_ini' => \App\Models\AbsensiSiswa::whereDate('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'siswa_alfa_hari_ini' => \App\Models\AbsensiSiswa::whereDate('tanggal', $today)->where('status', 'Alfa')->count(),
                'absensi_siswa_terbaru' => \App\Models\AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
                    ->whereDate('tanggal', $today)
                    ->latest('jam_masuk')
                    ->limit(10)
                    ->get(),
            ];
        });
    }

    public static function dashboardMonitoring(string $today): array
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $dayName = match ($now->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $data = Cache::remember("ks_dashboard_{$today}", 30, function () use ($today) {
            $totalGuru = Guru::count();
            $guruHadir = \App\Models\AbsensiGuru::where('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count();
            $guruTidakHadir = \App\Models\AbsensiGuru::where('tanggal', $today)->where('status', 'Alpha')->count();
            $guruSakit = \App\Models\AbsensiGuru::where('tanggal', $today)->where('status', 'Sakit')->count();
            $guruIzin = \App\Models\AbsensiGuru::where('tanggal', $today)->where('status', 'Izin')->count();
            $guruRecorded = \App\Models\AbsensiGuru::where('tanggal', $today)->count();
            $guruBelumAbsen = max(0, $totalGuru - $guruRecorded);

            $guruList = Guru::with('user')
                ->leftJoin('absensi_guru', function ($join) use ($today) {
                    $join->on('guru.id', '=', 'absensi_guru.guru_id')
                        ->where('absensi_guru.tanggal', '=', $today);
                })
                ->select(
                    'guru.*',
                    'absensi_guru.id as absensi_id',
                    'absensi_guru.status as status_hadir',
                    'absensi_guru.jam_masuk',
                    'absensi_guru.jam_keluar',
                    'absensi_guru.latitude',
                    'absensi_guru.longitude',
                    'absensi_guru.foto_lokasi'
                )
                ->orderByRaw("CASE WHEN absensi_guru.status IS NULL THEN 0 ELSE 1 END ASC, guru.nama_lengkap ASC")
                ->get()
                ->map(function ($g) {
                    $g->status_tampil = $g->status_hadir ?? 'Belum Absen';
                    return $g;
                });

            $totalSiswa = Siswa::count();

            $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
                ->where('tanggal', $today)
                ->groupBy('siswa_id');

            $dailyAbsensiBase = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            });

            $siswaHadir = (clone $dailyAbsensiBase)->whereIn('status', ['Hadir', 'Terlambat'])->count();
            $siswaAlfa = (clone $dailyAbsensiBase)->where('status', 'Alfa')->count();
            $siswaSakit = (clone $dailyAbsensiBase)->where('status', 'Sakit')->count();
            $siswaIzin = (clone $dailyAbsensiBase)->where('status', 'Izin')->count();
            $siswaTerlambat = (clone $dailyAbsensiBase)->where('status', 'Terlambat')->count();

            $rekapPerKelas = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                    $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
                })
                ->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
                ->selectRaw("siswa.kelas,
                    COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                    COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as terlambat,
                    COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as alfa,
                    COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as izin,
                    COUNT(*) as total")
                ->groupBy('siswa.kelas')
                ->orderBy('siswa.kelas')
                ->get();

            return array_merge([
                'totalGuru' => $totalGuru,
                'guruHadir' => $guruHadir,
                'guruTidakHadir' => $guruTidakHadir,
                'guruBelumAbsen' => $guruBelumAbsen,
                'guruSakit' => $guruSakit,
                'guruIzin' => $guruIzin,
                'guruList' => $guruList,
                'totalSiswa' => $totalSiswa,
                'siswaHadir' => $siswaHadir,
                'siswaAlfa' => $siswaAlfa,
                'siswaSakit' => $siswaSakit,
                'siswaIzin' => $siswaIzin,
                'siswaTerlambat' => $siswaTerlambat,
                'rekapPerKelas' => $rekapPerKelas,
            ], self::dashboardAdmin($today));
        });

        $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->where('tanggal', $today)
            ->groupBy('siswa_id');

        $allSiswaHariIni = Siswa::with('user')
            ->leftJoinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('siswa.id', '=', 'first_absensi.siswa_id');
            })
            ->leftJoin('absensi_siswa', function ($join) {
                $join->on('first_absensi.first_id', '=', 'absensi_siswa.id');
            })
            ->select(
                'siswa.id as siswa_id',
                'siswa.nama_lengkap',
                'siswa.nisn',
                'siswa.kelas',
                'absensi_siswa.status',
                'absensi_siswa.jam_masuk'
            )
            ->orderBy('siswa.kelas')
            ->orderBy('siswa.nama_lengkap')
            ->get()
            ->groupBy('kelas');

        $pengaturan = \App\Models\PengaturanSekolah::current();

        $currentMonth = $now->month;
        $currentYear = $now->year;

        $hadirBulanan = \App\Models\AbsensiGuru::whereMonth('tanggal', $currentMonth)
            ->whereYear('tanggal', $currentYear)
            ->whereIn('status', ['Hadir', 'Terlambat'])
            ->selectRaw('guru_id, count(*) as total')
            ->groupBy('guru_id')
            ->pluck('total', 'guru_id');

        $kpiGuru = Guru::with(['kpiBulanan' => function ($q) use ($currentMonth, $currentYear) {
            $q->where('bulan', $currentMonth)->where('tahun', $currentYear);
        }])->orderBy('nama_lengkap')->get()->map(function ($g) use ($hadirBulanan) {
            $hadir = (int) ($hadirBulanan[$g->id] ?? 0);
            $target = $g->kpiBulanan->first()?->target_hari ?? 0;
            $persentase = $target > 0 ? round(($hadir / $target) * 100) : 0;
            if ($persentase > 100) {
                $persentase = 100;
            }

            $g->kpi_hadir = $hadir;
            $g->kpi_target = $target;
            $g->kpi_persentase = $persentase;
            return $g;
        });

        return [
            'data' => $data,
            'allSiswaHariIni' => $allSiswaHariIni,
            'today' => $today,
            'hariAktif' => $dayName,
            'pengaturan' => $pengaturan,
            'kpiGuru' => $kpiGuru,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ];
    }
}
