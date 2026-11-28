<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\AssessmentItem;
use App\Models\ClassHomeroom;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporArchive;
use App\Models\RaporSetting;
use App\Models\ReportFinalization;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\OpenRouterService;
use App\Services\RaporArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CetakRaporController extends Controller
{
    /**
     * Resolve allowed homeroom classes for the authenticated user.
     * - Returns null for super_admin, admin_sekolah, kepala_sekolah (unrestricted).
     * - For role 'guru', returns array of classes where the teacher is assigned as Wali Kelas in ClassHomeroom,
     *   or aborts with 403 if the teacher is not assigned as a Wali Kelas.
     */
    protected function resolveAllowedHomeroomClasses(?int $tahunId = null, ?string $targetKelas = null): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'guru') {
            return null;
        }

        $guruId = $user->guru?->id;
        if (!$guruId) {
            abort(403, 'Akses Cetak Rapor hanya untuk Wali Kelas yang telah ditetapkan pada Pengaturan Format & TTD Rapor.');
        }

        $query = ClassHomeroom::where('guru_id', $guruId);
        if ($tahunId) {
            $yearClasses = (clone $query)->where('tahun_ajaran_id', $tahunId)->pluck('kelas')->filter()->unique()->values()->all();
            if (!empty($yearClasses)) {
                if ($targetKelas !== null && !in_array($targetKelas, $yearClasses, true)) {
                    abort(403, "Anda bukan Wali Kelas untuk rombel {$targetKelas}.");
                }
                return $yearClasses;
            }
        }

        $allAssignedClasses = $query->pluck('kelas')->filter()->unique()->values()->all();
        if (empty($allAssignedClasses)) {
            abort(403, 'Akses Cetak Rapor hanya untuk Wali Kelas yang telah ditetapkan pada Pengaturan Format & TTD Rapor.');
        }

        if ($targetKelas !== null && !in_array($targetKelas, $allAssignedClasses, true)) {
            abort(403, "Anda bukan Wali Kelas untuk rombel {$targetKelas}.");
        }

        return $allAssignedClasses;
    }

    /**
     * Display list of students ready for report card printing.
     */
    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $semesterRaw = ucfirst(strtolower(trim((string) $request->query('semester', 'Ganjil'))));
        $semester = ($semesterRaw === 'Genap') ? 'Genap' : 'Ganjil';
        $jenisRapor = ($semester === 'Genap') ? 'uas' : 'sts';

        $allowedHomerooms = $this->resolveAllowedHomeroomClasses($tahunId);
        $archiveService = app(RaporArchiveService::class);

        $classes = $archiveService->resolveClassesForPeriod($tahunId, $semester);
        if ($allowedHomerooms !== null) {
            $classes = $classes->filter(fn ($c) => in_array($c, $allowedHomerooms, true))->values();
        }

        $kelas = $request->query('kelas', $classes->first() ?? ($allowedHomerooms[0] ?? 'X AK'));
        if ($allowedHomerooms !== null && !in_array($kelas, $allowedHomerooms, true)) {
            $kelas = $allowedHomerooms[0];
        }

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $raporSettings = RaporSetting::instance();
        $schoolSettings = PengaturanSekolah::first();

        // Fetch cohort students using historical class first, fallback to current class
        $cohortIds = $archiveService->resolveCohortStudentIds($kelas, $tahunId, $semester);
        $students = Siswa::whereIn('id', $cohortIds)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        // Ensure class STS summary & rankings are up-to-date
        app(\App\Services\GradingCalculationService::class)->recalculateClassSTS($kelas, $tahunId, $semester);

        // Fetch STS Summaries for this class & period
        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        // Check finalization status & existing immutable archives
        $finalization = ReportFinalization::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->first();

        $archivedMap = RaporArchive::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy(fn ($a) => "{$a->siswa_id}_{$a->jenis_rapor}");

        // Total subjects relevant to this class (major + level + schedule)
        $totalSubjectsCount = MataPelajaran::forClass($kelas, $tahunId, $semester)->count();

        return view('pages.erapor.cetak-rapor', compact(
            'classes',
            'kelas',
            'allYears',
            'targetYear',
            'semester',
            'jenisRapor',
            'raporSettings',
            'schoolSettings',
            'students',
            'stsSummaries',
            'finalization',
            'archivedMap',
            'totalSubjectsCount'
        ));
    }

    /**
     * Finalize and freeze digital rapor archives for all students in a class & period.
     */
    public function archiveClass(Request $request)
    {
        $validated = $request->validate([
            'kelas' => 'required|string|max:50',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|in:Ganjil,Genap',
            'jenis_rapor' => 'nullable|in:all,sts,uas',
        ]);

        $kelas = $validated['kelas'];
        $tahunId = (int) $validated['tahun_ajaran_id'];
        $semester = $validated['semester'];
        $jenisScope = $validated['jenis_rapor'] ?? 'all';
        $jenisList = $jenisScope === 'all' ? ['sts', 'uas'] : [$jenisScope];

        ReportFinalization::updateOrCreate(
            [
                'kelas' => $kelas,
                'tahun_ajaran_id' => $tahunId,
                'semester' => $semester,
            ],
            [
                'status' => 'final',
                'reviewed_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'finalized_at' => now(),
                'notes' => 'Difinalisasi dan diarsipkan dari halaman Cetak Rapor.',
            ]
        );

        $count = app(RaporArchiveService::class)->archiveClass($kelas, $tahunId, $semester, $jenisList, auth()->id());

        return back()->with('success', "Berhasil mengarsipkan {$count} dokumen rapor digital kelas {$kelas} ({$semester}). Arsip kini terkunci permanen dengan QR verifikasi.");
    }

    /**
     * Form for Homeroom Teacher (Wali Kelas) to input and manage student notes.
     */
    public function catatanWaliKelas(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $semester = $request->query('semester', 'Ganjil');

        $allowedHomerooms = $this->resolveAllowedHomeroomClasses($tahunId);
        $archiveService = app(RaporArchiveService::class);

        $classes = $archiveService->resolveClassesForPeriod($tahunId, $semester);
        if ($allowedHomerooms !== null) {
            $classes = $classes->filter(fn ($c) => in_array($c, $allowedHomerooms, true))->values();
        }

        $kelas = $request->query('kelas', $classes->first() ?? ($allowedHomerooms[0] ?? 'X AK'));
        if ($allowedHomerooms !== null && !in_array($kelas, $allowedHomerooms, true)) {
            $kelas = $allowedHomerooms[0];
        }

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $cohortIds = $archiveService->resolveCohortStudentIds($kelas, $tahunId, $semester);

        $students = Siswa::whereIn('id', $cohortIds)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $attQuery = AbsensiSiswa::whereIn('siswa_id', $students->pluck('id'));
        $dateRange = $archiveService->resolveSemesterDateRange($targetYear, $semester);
        if ($dateRange !== null) {
            $attQuery->whereBetween('tanggal', $dateRange);
        }
        $attendances = $attQuery->selectRaw("
                siswa_id,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->groupBy('siswa_id')
            ->get()
            ->keyBy('siswa_id');

        return view('pages.erapor.catatan-wali-kelas', compact(
            'classes',
            'kelas',
            'allYears',
            'targetYear',
            'semester',
            'students',
            'stsSummaries',
            'attendances'
        ));
    }

    /**
     * Save Homeroom Teacher (Wali Kelas) notes for students.
     */
    public function saveCatatanWaliKelas(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'kelas' => 'required|string',
            'catatan' => 'array',
            'ekskul' => 'nullable|array',
            'keputusan_status' => 'nullable|array',
            'keputusan_status.*' => 'nullable|string|in:naik,tinggal,lulus,tidak_lulus',
            'keputusan_kelas' => 'nullable|array',
            'keputusan_kelas.*' => 'nullable|string|max:60',
        ]);

        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = $request->input('semester');
        $this->resolveAllowedHomeroomClasses($tahunId, $request->input('kelas'));
        $catatanList = $request->input('catatan', []);
        $ekskulInput = $request->input('ekskul', []);
        $keputusanStatusInput = $request->input('keputusan_status', []);
        $keputusanKelasInput = $request->input('keputusan_kelas', []);

        $hasEkskulInput = $request->has('ekskul');
        $hasKeputusanStatusInput = $request->has('keputusan_status');
        $hasKeputusanKelasInput = $request->has('keputusan_kelas');

        $allStudentIds = collect(array_keys($catatanList))
            ->merge(array_keys($ekskulInput))
            ->merge(array_keys($keputusanStatusInput))
            ->merge(array_keys($keputusanKelasInput))
            ->unique()
            ->values();

        foreach ($allStudentIds as $siswaId) {
            $payload = [];

            if (array_key_exists($siswaId, $catatanList)) {
                $payload['catatan_wali_kelas'] = trim((string) ($catatanList[$siswaId] ?? ''));
            }

            if ($hasEkskulInput) {
                $rows = $ekskulInput[$siswaId] ?? [];
                $cleanedEkskul = [];
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $nama = trim((string) ($row['nama'] ?? ''));
                        $ket = trim((string) ($row['keterangan'] ?? ''));
                        if ($nama === '' && $ket === '') {
                            continue;
                        }
                        if ($nama === '-' && $ket === '-') {
                            continue;
                        }
                        $cleanedEkskul[] = [
                            'nama' => $nama !== '' ? $nama : '-',
                            'keterangan' => $ket !== '' ? $ket : '-',
                        ];
                    }
                }
                $payload['ekstrakurikuler'] = $cleanedEkskul;
            }

            if ($hasKeputusanStatusInput) {
                $statusVal = trim((string) ($keputusanStatusInput[$siswaId] ?? ''));
                $payload['keputusan_status'] = $statusVal !== '' ? $statusVal : null;
            }

            if ($hasKeputusanKelasInput) {
                $kelasVal = trim((string) ($keputusanKelasInput[$siswaId] ?? ''));
                $payload['keputusan_kelas'] = $kelasVal !== '' ? $kelasVal : null;
            }

            if (!empty($payload)) {
                $payload['kelas'] = $request->input('kelas');
                StudentStsSummary::updateOrCreate(
                    [
                        'siswa_id' => $siswaId,
                        'tahun_ajaran_id' => $tahunId,
                        'semester' => $semester,
                    ],
                    $payload
                );
            }
        }

        // Auto-sync existing archives ONLY for active academic year & active students (keep alumni/past years immutable)
        $targetYear = TahunAjaran::find($tahunId);
        if ($targetYear?->is_aktif && $allStudentIds->isNotEmpty()) {
            $archiveService = app(RaporArchiveService::class);
            $activeStudents = Siswa::whereIn('id', $allStudentIds)
                ->where(function ($q) {
                    $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                })
                ->get()
                ->keyBy('id');

            if ($activeStudents->isNotEmpty()) {
                $existingArchives = RaporArchive::whereIn('siswa_id', $activeStudents->keys())
                    ->where('tahun_ajaran_id', $tahunId)
                    ->where('semester', $semester)
                    ->get();

                foreach ($existingArchives as $arch) {
                    $stModel = $activeStudents->get($arch->siswa_id);
                    if ($stModel) {
                        $archiveService->archiveStudent($stModel, $tahunId, $semester, $arch->jenis_rapor, auth()->id(), recalculate: false);
                    }
                }
            }
        }

        return redirect()->route('erapor.cetak.catatan-wali', [
            'kelas' => $request->input('kelas'),
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Catatan Wali Kelas, Ekstrakurikuler & Keputusan Rapor berhasil disimpan dan langsung disinkronkan.');
    }

    /**
     * Generate AI-powered note for a single student via OpenRouter.
     */
    public function generateAiCatatan(Request $request, OpenRouterService $aiService)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $siswa = Siswa::findOrFail($request->input('siswa_id'));
        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = $request->input('semester');
        $archiveService = app(RaporArchiveService::class);
        $kelasHistoris = $archiveService->resolveHistoricalClass($siswa, $tahunId, $semester);
        $this->resolveAllowedHomeroomClasses($tahunId, $kelasHistoris);

        $stsSummary = StudentStsSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->first();

        $attQuery = AbsensiSiswa::where('siswa_id', $siswa->id);
        $dateRange = $archiveService->resolveSemesterDateRange(TahunAjaran::find($tahunId), $semester);
        if ($dateRange !== null) {
            $attQuery->whereBetween('tanggal', $dateRange);
        }
        $att = $attQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        $attendance = [
            's' => (int) ($att->s ?? 0),
            'i' => (int) ($att->i ?? 0),
            'a' => (int) ($att->a ?? 0),
        ];

        $aiNote = $aiService->generateCatatanWaliKelas(
            $siswa,
            $stsSummary?->average_score,
            $stsSummary?->rank,
            $attendance
        );

        return response()->json([
            'success' => true,
            'catatan' => $aiNote,
            'siswa_id' => $siswa->id,
            'siswa_nama' => $siswa->nama_lengkap,
        ]);
    }

    /**
     * Display configuration interface for report card formatting & homeroom signatures.
     */
    public function pengaturan(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;

        $raporSettings = RaporSetting::instance();

        // Get all unique classes registered in students
        $classes = \App\Models\Rombel::classNames($tahunId);

        // All teachers for homeroom dropdown
        $teachers = Guru::orderBy('nama_lengkap', 'asc')->get();

        // Existing homeroom configurations for this academic year
        $homerooms = ClassHomeroom::where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->get()
            ->keyBy('kelas');

        // School default headmaster
        $defaultKepalaSekolah = User::where('role', 'kepala_sekolah')->first();

        // All subjects for KKM configuration
        $subjects = MataPelajaran::orderBy('nama', 'asc')->get();

        return view('pages.erapor.pengaturan-cetak-rapor', compact(
            'raporSettings',
            'allYears',
            'targetYear',
            'classes',
            'teachers',
            'homerooms',
            'defaultKepalaSekolah',
            'subjects'
        ));
    }

    /**
     * Update global report card document format and headmaster info.
     */
    public function updatePengaturan(Request $request)
    {
        $request->validate([
            'nama_kurikulum' => 'nullable|string|max:100',
            'tipe_judul_rapor' => 'nullable|string|in:template,custom',
            'judul_rapor_sts' => 'nullable|string|max:191',
            'judul_rapor_akhir' => 'nullable|string|max:191',
            'fase_text' => 'nullable|string|max:50',
            'ukuran_kertas' => 'nullable|string|in:A4,F4,Letter',
            'default_kkm' => 'nullable|numeric|min:0|max:100',
            'class_kkm' => 'nullable|array',
            'class_kkm.*' => 'nullable|numeric|min:0|max:100',
            'mapel_kkm' => 'nullable|array',
            'mapel_kkm.*' => 'nullable|numeric|min:0|max:100',
            'kota_pembagian' => 'required|string|max:100',
            'tanggal_rapor' => 'nullable|date',
            'nama_kepala_sekolah' => 'nullable|string|max:150',
            'nip_kepala_sekolah' => 'nullable|string|max:50',
            'header_yayasan' => 'nullable|string|max:150',
            'header_sekolah' => 'nullable|string|max:150',
            'ttd_kepala_sekolah' => 'nullable|image|max:2048',
            'stempel_sekolah' => 'nullable|image|max:2048',
            'logo_yayasan' => 'nullable|image|max:2048',
        ]);

        $settings = RaporSetting::instance();

        $data = [
            'nama_kurikulum' => $request->input('nama_kurikulum', 'Kurikulum Merdeka'),
            'tipe_judul_rapor' => $request->input('tipe_judul_rapor', 'template'),
            'judul_rapor_sts' => $request->input('judul_rapor_sts'),
            'judul_rapor_akhir' => $request->input('judul_rapor_akhir'),
            'show_kurikulum' => $request->has('show_kurikulum'),
            'show_fase' => $request->has('show_fase'),
            'fase_text' => $request->input('fase_text', 'Fase D'),
            'ukuran_kertas' => $request->input('ukuran_kertas', 'A4'),
            'show_ranking' => $request->has('show_ranking'),
            'show_kkm' => $request->has('show_kkm'),
            'default_kkm' => $request->input('default_kkm', 75),
            'kota_pembagian' => $request->input('kota_pembagian', 'Bogor'),
            'tanggal_rapor' => $request->input('tanggal_rapor'),
            'nama_kepala_sekolah' => $request->input('nama_kepala_sekolah'),
            'nip_kepala_sekolah' => $request->input('nip_kepala_sekolah'),
            'header_yayasan' => $request->input('header_yayasan'),
            'header_sekolah' => $request->input('header_sekolah'),
        ];

        // Save KKM per class if provided in Section 1
        $targetTahunId = (int) $request->input('tahun_ajaran_id', 0);
        if ($targetTahunId <= 0) {
            $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
            $targetTahunId = (int) ($activeYear?->id ?? 1);
        }

        if ($request->has('class_kkm') && is_array($request->class_kkm)) {
            foreach ($request->class_kkm as $cls => $kkmVal) {
                if ($kkmVal !== null && $kkmVal !== '') {
                    $hr = ClassHomeroom::firstOrNew([
                        'kelas' => $cls,
                        'tahun_ajaran_id' => $targetTahunId,
                    ]);
                    $hr->kkm_default = (float) $kkmVal;
                    $hr->save();
                }
            }
        }

        if ($request->has('mapel_kkm') && is_array($request->mapel_kkm)) {
            foreach ($request->mapel_kkm as $mapelId => $kkmVal) {
                if ($kkmVal !== null && $kkmVal !== '') {
                    MataPelajaran::where('id', $mapelId)->update(['kkm' => (float) $kkmVal]);
                }
            }
        }

        if ($request->hasFile('ttd_kepala_sekolah')) {
            if ($settings->ttd_kepala_sekolah && Storage::disk('public')->exists($settings->ttd_kepala_sekolah)) {
                Storage::disk('public')->delete($settings->ttd_kepala_sekolah);
            }
            $data['ttd_kepala_sekolah'] = $request->file('ttd_kepala_sekolah')->store('rapor/ttd', 'public');
        }

        if ($request->hasFile('stempel_sekolah')) {
            if ($settings->stempel_sekolah && Storage::disk('public')->exists($settings->stempel_sekolah)) {
                Storage::disk('public')->delete($settings->stempel_sekolah);
            }
            $data['stempel_sekolah'] = $request->file('stempel_sekolah')->store('rapor/stempel', 'public');
        }

        if ($request->hasFile('logo_yayasan')) {
            if ($settings->logo_yayasan && Storage::disk('public')->exists($settings->logo_yayasan)) {
                Storage::disk('public')->delete($settings->logo_yayasan);
            }
            $data['logo_yayasan'] = $request->file('logo_yayasan')->store('rapor/logo', 'public');
        }

        $settings->update($data);

        return redirect()->route('erapor.cetak.pengaturan')->with('success', 'Format dokumen rapor & pengesahan kepala sekolah berhasil diperbarui.');
    }

    /**
     * Update homeroom teachers & digital signatures per class.
     */
    public function updateHomerooms(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'homerooms' => 'array',
            'homerooms.*.kkm_default' => 'nullable|numeric|min:0|max:100',
            'homerooms.*.kkm_mapel' => 'nullable|array',
            'homerooms.*.kkm_mapel.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $tahunId = (int) $request->input('tahun_ajaran_id');
        $homeroomsInput = $request->input('homerooms', []);

        foreach ($homeroomsInput as $kelas => $row) {
            $homeroom = ClassHomeroom::firstOrNew([
                'kelas' => $kelas,
                'tahun_ajaran_id' => $tahunId,
            ]);

            $guruId = !empty($row['guru_id']) ? (int) $row['guru_id'] : null;
            $selectedGuru = $guruId ? Guru::find($guruId) : null;

            $homeroom->guru_id = $guruId;
            $homeroom->nama_wali_kelas = !empty($row['nama_wali_kelas']) 
                ? trim($row['nama_wali_kelas']) 
                : ($selectedGuru?->nama_lengkap ?? null);
            $homeroom->nip_wali_kelas = !empty($row['nip_wali_kelas']) 
                ? trim($row['nip_wali_kelas']) 
                : ($selectedGuru?->nip ?? null);

            // KKM Standar Kelas
            if (array_key_exists('kkm_default', $row) && $row['kkm_default'] !== null && $row['kkm_default'] !== '') {
                $homeroom->kkm_default = (float) $row['kkm_default'];
            }

            // KKM Khusus Per Mapel pada Kelas ini
            if (isset($row['kkm_mapel']) && is_array($row['kkm_mapel'])) {
                $cleanMap = [];
                foreach ($row['kkm_mapel'] as $mId => $mVal) {
                    if ($mVal !== null && $mVal !== '') {
                        $cleanMap[$mId] = (float) $mVal;
                    }
                }
                $homeroom->kkm_mapel = !empty($cleanMap) ? $cleanMap : null;
            }

            // Handle TTD file upload if present
            if ($request->hasFile("homerooms.{$kelas}.ttd_file")) {
                if ($homeroom->ttd_wali_kelas && Storage::disk('public')->exists($homeroom->ttd_wali_kelas)) {
                    Storage::disk('public')->delete($homeroom->ttd_wali_kelas);
                }
                $path = $request->file("homerooms.{$kelas}.ttd_file")->store('rapor/ttd/wali', 'public');
                $homeroom->ttd_wali_kelas = $path;
            }

            $homeroom->save();
        }

        return redirect()->route('erapor.cetak.pengaturan', ['tahun_ajaran_id' => $tahunId])
            ->with('success', 'Daftar Wali Kelas, Nilai KKM Kelas, dan Tanda Tangan per rombel berhasil diperbarui.');
    }

    /**
     * Print single student formal report card (A4 portrait), serving immutable RaporArchive snapshot when available.
     */
    public function printSiswa(Request $request, Siswa $siswa)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $tahunId = (int) $request->query('tahun_ajaran_id', $siswa->tahun_ajaran_id ?? $activeYear?->id ?? 1);
        $semesterRaw = ucfirst(strtolower(trim((string) $request->query('semester', 'Ganjil'))));
        $semester = ($semesterRaw === 'Genap') ? 'Genap' : 'Ganjil';
        $defaultJenis = ($semester === 'Genap') ? 'uas' : 'sts';
        $jenisRapor = $request->query('jenis_rapor', $defaultJenis);
        if ($jenisRapor === 'akhir') {
            $jenisRapor = 'uas';
        }

        $archiveService = app(RaporArchiveService::class);
        $kelasHistoris = $archiveService->resolveHistoricalClass($siswa, $tahunId, $semester);
        $this->resolveAllowedHomeroomClasses($tahunId, $kelasHistoris);

        $preferArchive = $request->query('mode') !== 'live';
        $viewData = $archiveService->resolveViewDataForPrint(
            $siswa,
            $tahunId,
            $semester,
            $jenisRapor,
            preferArchive: $preferArchive,
            trackReprint: true
        );

        return view('pages.erapor.cetak-rapor-print', $viewData);
    }

    /**
     * Bulk print all students report card in a class (A4 portrait with page-break).
     */
    public function printKelas(Request $request, $kelas)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $this->resolveAllowedHomeroomClasses($tahunId, $kelas);
        $semesterRaw = ucfirst(strtolower(trim((string) $request->query('semester', 'Ganjil'))));
        $semester = ($semesterRaw === 'Genap') ? 'Genap' : 'Ganjil';
        $defaultJenis = ($semester === 'Genap') ? 'uas' : 'sts';
        $jenisRapor = $request->query('jenis_rapor', $defaultJenis);
        if ($jenisRapor === 'akhir') {
            $jenisRapor = 'uas';
        }

        $archiveService = app(RaporArchiveService::class);
        $preferArchive = $request->query('mode') !== 'live';
        $cohortIds = $archiveService->resolveCohortStudentIds($kelas, $tahunId, $semester);
        $students = Siswa::whereIn('id', $cohortIds)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $schoolSettings = PengaturanSekolah::first();
        $raporSettings = RaporSetting::instance();
        $homeroom = ClassHomeroom::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first();
        $waliKelas = $homeroom?->guru ?? Jadwal::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first()?->guru;
        $kepalaSekolah = User::where('role', 'kepala_sekolah')->with('guru')->first();
        $tanggalRaporFormatted = $raporSettings->tanggal_rapor
            ? $raporSettings->tanggal_rapor->translatedFormat('d F Y')
            : date('d F Y');
        $totalSiswaKelas = $students->count();
        $showKkm = (bool) ($raporSettings->show_kkm ?? false);
        $rataRataKelas = null;

        $raporData = [];
        foreach ($students as $siswa) {
            $item = $archiveService->resolveViewDataForPrint(
                $siswa,
                $tahunId,
                $semester,
                $jenisRapor,
                preferArchive: $preferArchive,
                trackReprint: true
            );
            $rataRataKelas = $item['rataRataKelas'] ?? $rataRataKelas;
            $raporData[] = $item;
        }

        return view('pages.erapor.cetak-rapor-kelas-print', compact(
            'kelas',
            'targetYear',
            'semester',
            'jenisRapor',
            'schoolSettings',
            'raporSettings',
            'showKkm',
            'homeroom',
            'tanggalRaporFormatted',
            'raporData',
            'rataRataKelas',
            'waliKelas',
            'kepalaSekolah',
            'totalSiswaKelas'
        ));
    }

    /**
     * Determine predicate letter based on score.
     */
    public static function calculatePredikat(?float $score): string
    {
        if ($score === null) {
            return '-';
        }
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        return 'D';
    }

    /**
     * Generate Kurikulum Merdeka competence description based on predicate.
     */
    public static function generateDeskripsi(string $mapelNama, ?float $score, string $predikat): string
    {
        if ($score === null || $predikat === '-') {
            return "Belum ada penilaian tercatat untuk mata pelajaran {$mapelNama}.";
        }

        return match ($predikat) {
            'A' => "Menunjukkan penguasaan yang sangat baik dengan pemahaman materi {$mapelNama} secara mendalam dan melampaui kriteria ketercapaian tujuan pembelajaran.",
            'B' => "Menunjukkan penguasaan yang baik dengan pemahaman materi {$mapelNama} secara menyeluruh serta telah mencapai tujuan pembelajaran yang ditetapkan.",
            'C' => "Menunjukkan pemahaman yang cukup baik pada materi {$mapelNama} dan telah mencapai tujuan pembelajaran dasar, disarankan untuk terus berlatih memperdalam materi.",
            'D' => "Memerlukan bimbingan dan pendampingan lebih lanjut untuk meningkatkan pemahaman materi {$mapelNama} agar dapat mencapai kriteria ketercapaian tujuan pembelajaran.",
            default => "Capaian pembelajaran materi {$mapelNama} memenuhi standar minimal.",
        };
    }
}
