<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\TanggalAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmartApiController extends Controller
{
    /**
     * Map local Indonesian status to canonical PRD status
     */
    private function mapCanonicalStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'hadir' => 'present',
            'terlambat' => 'late',
            'sakit' => 'sick',
            'izin' => 'permission',
            'alfa' => 'unexcused_absence',
            default => strtolower(trim($status)),
        };
    }

    /**
     * Map canonical status to local status for filtering
     */
    private function mapLocalStatus(string $canonical): string
    {
        return match (strtolower(trim($canonical))) {
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'sick' => 'Sakit',
            'permission' => 'Izin',
            'unexcused_absence', 'unexcused' => 'Alfa',
            default => ucfirst(trim($canonical)),
        };
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'smart-plus',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * List students with metadata and filters (PRD 9.2)
     */
    public function getStudents(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = Siswa::with(['tahunAjaran', 'rombel']);

        if (!$request->boolean('include_inactive')) {
            $query->aktif();
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->string('kelas'));
        }

        if ($request->filled('rombel_id')) {
            $query->where('rombel_id', $request->integer('rombel_id'));
        }

        if ($request->filled('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->integer('tahun_ajaran_id'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nipd', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $paginator = $query->orderBy('nama_lengkap')->paginate($perPage);

        $items = collect($paginator->items())->map(function (Siswa $siswa) {
            return [
                'id' => $siswa->id,
                'external_id' => (string) $siswa->id,
                'dapodik_peserta_didik_id' => $siswa->dapodik_peserta_didik_id,
                'nipd' => $siswa->nipd,
                'nisn' => $siswa->nisn,
                'nis' => $siswa->nipd ?: $siswa->nisn,
                'nama' => $siswa->nama_lengkap,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'rombel_id' => $siswa->rombel_id,
                'kelas' => $siswa->kelas,
                'status_siswa' => $siswa->status_siswa ?? 'aktif',
                'tahun_ajaran' => [
                    'id' => $siswa->tahun_ajaran_id,
                    'nama' => $siswa->tahunAjaran?->nama,
                    'is_aktif' => (bool) $siswa->tahunAjaran?->is_aktif,
                ],
                'updated_at' => $siswa->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * List active classes and statistics (PRD 9.2)
     */
    public function getClasses(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $activeYear = TahunAjaran::where('is_aktif', true)->first();

        $studentCounts = Siswa::aktif()
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->select('kelas', DB::raw('count(*) as total_siswa'))
            ->groupBy('kelas')
            ->pluck('total_siswa', 'kelas');

        $rombels = \App\Models\Rombel::where('is_aktif', true)
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get()
            ->keyBy('nama');

        $allClassNames = $rombels->keys()
            ->merge($studentCounts->keys())
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $classes = $allClassNames->map(function (string $className) use ($rombels, $studentCounts, $activeYear) {
            $rombel = $rombels->get($className);
            $tingkat = $rombel?->tingkat;
            if (!$tingkat) {
                preg_match('/(XII|XI|IX|VIII|VII|X|\d+)/i', $className, $matches);
                $tingkat = isset($matches[0]) ? strtoupper($matches[0]) : '-';
            }

            return [
                'id' => $className,
                'rombel_id' => $rombel?->id,
                'dapodik_rombongan_belajar_id' => $rombel?->dapodik_rombongan_belajar_id,
                'external_id' => $className,
                'nama_kelas' => $className,
                'tingkat' => $tingkat,
                'kompetensi_keahlian' => $rombel?->kompetensi_keahlian,
                'total_siswa' => (int) ($studentCounts[$className] ?? 0),
                'tahun_ajaran' => $rombel?->tahunAjaran?->nama ?? $activeYear?->nama,
                'status' => 'aktif',
            ];
        });

        return response()->json([
            'data' => $classes,
            'meta' => [
                'total' => $classes->count(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * List finalized daily attendance records (PRD 9.2 & 9.3)
     */
    public function getAttendanceRecords(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = AbsensiSiswa::with('siswa');

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->date('to'));
        }

        if ($request->filled('student_id')) {
            $studentSearch = $request->string('student_id');
            $query->whereHas('siswa', function ($q) use ($studentSearch) {
                $q->where('id', $studentSearch)
                  ->orWhere('nisn', $studentSearch);
            });
        }

        if ($request->filled('class_id') || $request->filled('kelas')) {
            $kelas = $request->get('class_id', $request->get('kelas'));
            $query->whereHas('siswa', fn ($q) => $q->where('kelas', $kelas));
        }

        if ($request->filled('status')) {
            $statusInput = $request->string('status');
            $localStatus = $this->mapLocalStatus($statusInput);
            $query->where('status', $localStatus);
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $paginator = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (AbsensiSiswa $record) {
            $siswa = $record->siswa;
            $canonicalStatus = $this->mapCanonicalStatus($record->status);

            return [
                'id' => 'attrec_' . $record->id,
                'student_external_id' => (string) $record->siswa_id,
                'student_nisn' => $siswa?->nisn,
                'student_nis' => $siswa?->nisn,
                'student_name' => $siswa?->nama_lengkap,
                'class_external_id' => (string) $siswa?->kelas,
                'class_name' => $siswa?->kelas,
                'attendance_date' => $record->tanggal?->format('Y-m-d'),
                'status' => $canonicalStatus,
                'check_in_at' => $record->jam_masuk ? $record->tanggal?->format('Y-m-d') . 'T' . $record->jam_masuk->format('H:i:s') : null,
                'check_out_at' => $record->jam_keluar ? $record->tanggal?->format('Y-m-d') . 'T' . $record->jam_keluar->format('H:i:s') : null,
                'absence_reason' => $record->keterangan,
                'is_final' => true,
                'updated_at' => $record->updated_at?->toIso8601String(),
                'source' => 'rfid',
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Single attendance record detail
     */
    public function getAttendanceRecordDetail($id): JsonResponse
    {
        $numericId = (int) str_replace('attrec_', '', $id);
        $record = AbsensiSiswa::with('siswa')->find($numericId);

        $requestId = 'req_' . Str::uuid();

        if (!$record) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Catatan absensi tidak ditemukan.',
                    'details' => ['record_id' => $id],
                    'request_id' => $requestId,
                ]
            ], 404);
        }

        $siswa = $record->siswa;
        $canonicalStatus = $this->mapCanonicalStatus($record->status);

        return response()->json([
            'data' => [
                'id' => 'attrec_' . $record->id,
                'student_external_id' => (string) $record->siswa_id,
                'student_nisn' => $siswa?->nisn,
                'student_nis' => $siswa?->nisn,
                'student_name' => $siswa?->nama_lengkap,
                'class_external_id' => (string) $siswa?->kelas,
                'class_name' => $siswa?->kelas,
                'attendance_date' => $record->tanggal?->format('Y-m-d'),
                'status' => $canonicalStatus,
                'check_in_at' => $record->jam_masuk ? $record->tanggal?->format('Y-m-d') . 'T' . $record->jam_masuk->format('H:i:s') : null,
                'check_out_at' => $record->jam_keluar ? $record->tanggal?->format('Y-m-d') . 'T' . $record->jam_keluar->format('H:i:s') : null,
                'absence_reason' => $record->keterangan,
                'is_final' => true,
                'updated_at' => $record->updated_at?->toIso8601String(),
                'source' => 'rfid',
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Aggregated attendance summaries per student/class for E-Rapor (PRD 9.2)
     */
    public function getAttendanceSummaries(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $studentsQuery = Siswa::query();

        if ($request->filled('kelas')) {
            $studentsQuery->where('kelas', $request->string('kelas'));
        }

        if ($request->filled('student_id')) {
            $studentSearch = $request->string('student_id');
            $studentsQuery->where(function ($q) use ($studentSearch) {
                $q->where('id', $studentSearch)
                  ->orWhere('nisn', $studentSearch);
            });
        }

        $students = $studentsQuery->orderBy('nama_lengkap')->get();

        $fromDate = $request->filled('from') ? $request->date('from') : null;
        $toDate = $request->filled('to') ? $request->date('to') : null;

        $summaries = $students->map(function (Siswa $siswa) use ($fromDate, $toDate) {
            $attQuery = AbsensiSiswa::where('siswa_id', $siswa->id);

            if ($fromDate) {
                $attQuery->whereDate('tanggal', '>=', $fromDate);
            }
            if ($toDate) {
                $attQuery->whereDate('tanggal', '<=', $toDate);
            }

            $counts = $attQuery->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $hadir = (int) ($counts['Hadir'] ?? 0);
            $terlambat = (int) ($counts['Terlambat'] ?? 0);
            $sakit = (int) ($counts['Sakit'] ?? 0);
            $izin = (int) ($counts['Izin'] ?? 0);
            $alfa = (int) ($counts['Alfa'] ?? 0);
            $total = $hadir + $terlambat + $sakit + $izin + $alfa;

            return [
                'student_id' => $siswa->id,
                'student_external_id' => (string) $siswa->id,
                'student_nisn' => $siswa->nisn,
                'student_name' => $siswa->nama_lengkap,
                'class_name' => $siswa->kelas,
                'counts' => [
                    'present' => $hadir,
                    'late' => $terlambat,
                    'sick' => $sakit,
                    'permission' => $izin,
                    'unexcused_absence' => $alfa,
                    'total_recorded_days' => $total,
                ],
                'report_card_fields' => [
                    'sakit' => $sakit,
                    'izin' => $izin,
                    'tanpa_keterangan' => $alfa,
                ],
                'attendance_percentage' => $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'data' => $summaries,
            'meta' => [
                'total' => $summaries->count(),
                'from' => $fromDate?->format('Y-m-d'),
                'to' => $toDate?->format('Y-m-d'),
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * School Calendar & Holidays to prevent false absences (PRD 9.2)
     */
    public function getAttendanceCalendar(Request $request): JsonResponse
    {
        $requestId = 'req_' . Str::uuid();

        $query = HariLibur::query();

        if ($request->filled('from')) {
            $query->whereDate('tanggal_mulai', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal_selesai', '<=', $request->date('to'));
        }

        $holidays = $query->orderBy('tanggal_mulai')->get()->map(function (HariLibur $libur) {
            return [
                'id' => $libur->id,
                'nama' => $libur->nama,
                'tanggal_mulai' => $libur->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $libur->tanggal_selesai?->format('Y-m-d'),
                'keterangan' => $libur->keterangan,
            ];
        });

        return response()->json([
            'data' => $holidays,
            'meta' => [
                'total' => $holidays->count(),
                'request_id' => $requestId,
            ],
        ]);
    }
}
