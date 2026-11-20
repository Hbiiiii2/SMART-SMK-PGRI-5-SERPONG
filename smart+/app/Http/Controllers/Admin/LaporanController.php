<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AbsensiGuruExport;
use App\Exports\AbsensiSiswaExport;
use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Siswa;
use App\Services\CacheService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    use LogsActivity;
    public function index(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $tahunAjaranAktif = \App\Models\TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranFilter = $request->tahun_ajaran_id ?? ($tahunAjaranAktif ? $tahunAjaranAktif->id : null);

        $querySiswa = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran');
        $queryGuru = AbsensiGuru::with('guru.user');

        if ($tahunAjaranFilter) {
            $querySiswa->where(function ($outer) use ($tahunAjaranFilter) {
                $outer->whereHas('jadwal', function ($q) use ($tahunAjaranFilter) {
                    $q->where('tahun_ajaran_id', $tahunAjaranFilter);
                })->orWhere(function ($sub) use ($tahunAjaranFilter) {
                    $sub->whereNull('jadwal_id')->whereHas('siswa', function ($s) use ($tahunAjaranFilter) {
                        $s->where('tahun_ajaran_id', $tahunAjaranFilter);
                    });
                });
            });
        }

        if ($startDate) {
            $querySiswa->where('tanggal', '>=', $startDate);
            $queryGuru->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $querySiswa->where('tanggal', '<=', $endDate);
            $queryGuru->where('tanggal', '<=', $endDate);
        }

        $absensiSiswa = $querySiswa->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();
        $absensiGuru = $queryGuru->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();

        $rekapSiswaQuery = AbsensiSiswa::query();
        if ($tahunAjaranFilter) {
            $rekapSiswaQuery->where(function ($outer) use ($tahunAjaranFilter) {
                $outer->whereHas('jadwal', function ($q) use ($tahunAjaranFilter) {
                    $q->where('tahun_ajaran_id', $tahunAjaranFilter);
                })->orWhere(function ($sub) use ($tahunAjaranFilter) {
                    $sub->whereNull('jadwal_id')->whereHas('siswa', function ($s) use ($tahunAjaranFilter) {
                        $s->where('tahun_ajaran_id', $tahunAjaranFilter);
                    });
                });
            });
        }
        $rekapSiswa = $rekapSiswaQuery->selectRaw('status, count(*) as total')
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->groupBy('status')
            ->pluck('total', 'status');

        $rekapGuru = AbsensiGuru::selectRaw('status, count(*) as total')
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->groupBy('status')
            ->pluck('total', 'status');

        $tahunAjarans = \App\Models\TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();

        return view('pages.admin.laporan.index', [
            'title' => 'Laporan Kehadiran',
            'absensiSiswa' => $absensiSiswa,
            'absensiGuru' => $absensiGuru,
            'rekapSiswa' => $rekapSiswa,
            'rekapGuru' => $rekapGuru,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tahunAjarans' => $tahunAjarans,
            'tahunAjaranFilter' => $tahunAjaranFilter,
        ]);
    }

    public function exportSiswa(Request $request)
    {
        CacheService::flushDropdowns();

        $filename = 'laporan-absensi-siswa-' . ($request->start_date ?? 'semua') . '.xlsx';

        $this->logActivity('export_siswa', 'Export laporan siswa', $request);

        return Excel::download(
            new AbsensiSiswaExport($request->start_date, $request->end_date, $request->jadwal_id),
            $filename
        );
    }

    public function exportGuru(Request $request)
    {
        CacheService::flushDropdowns();

        $filename = 'laporan-absensi-guru-' . ($request->start_date ?? 'semua') . '.xlsx';

        $this->logActivity('export_guru', 'Export laporan guru', $request);

        return Excel::download(
            new AbsensiGuruExport($request->start_date, $request->end_date),
            $filename
        );
    }
}
