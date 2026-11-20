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
use App\Models\RaporSetting;
use App\Models\ReportFinalization;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\OpenRouterService;
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

        $allowedHomerooms = $this->resolveAllowedHomeroomClasses($tahunId);

        $classes = Siswa::select('kelas')->distinct()->whereNotNull('kelas')->orderBy('kelas')->pluck('kelas');
        if ($allowedHomerooms !== null) {
            $classes = $classes->filter(fn ($c) => in_array($c, $allowedHomerooms, true))->values();
        }

        $kelas = $request->query('kelas', $classes->first() ?? ($allowedHomerooms[0] ?? '7-A'));
        if ($allowedHomerooms !== null && !in_array($kelas, $allowedHomerooms, true)) {
            $kelas = $allowedHomerooms[0];
        }
        $semester = $request->query('semester', 'Ganjil');
        $jenisRapor = $request->query('jenis_rapor', 'sts'); // sts | uas
        if ($jenisRapor === 'akhir') {
            $jenisRapor = 'uas';
        }

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $raporSettings = RaporSetting::instance();

        // Fetch students in class
        $students = Siswa::where('kelas', $kelas)
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

        // Check finalization status
        $finalization = ReportFinalization::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->first();

        // Total subjects for completeness check
        $totalSubjectsCount = MataPelajaran::count();

        return view('pages.erapor.cetak-rapor', compact(
            'classes',
            'kelas',
            'allYears',
            'targetYear',
            'semester',
            'jenisRapor',
            'raporSettings',
            'students',
            'stsSummaries',
            'finalization',
            'totalSubjectsCount'
        ));
    }

    /**
     * Form for Homeroom Teacher (Wali Kelas) to input and manage student notes.
     */
    public function catatanWaliKelas(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);

        $allowedHomerooms = $this->resolveAllowedHomeroomClasses($tahunId);

        $classes = Siswa::select('kelas')->distinct()->whereNotNull('kelas')->orderBy('kelas')->pluck('kelas');
        if ($allowedHomerooms !== null) {
            $classes = $classes->filter(fn ($c) => in_array($c, $allowedHomerooms, true))->values();
        }

        $kelas = $request->query('kelas', $classes->first() ?? ($allowedHomerooms[0] ?? '7-A'));
        if ($allowedHomerooms !== null && !in_array($kelas, $allowedHomerooms, true)) {
            $kelas = $allowedHomerooms[0];
        }
        $semester = $request->query('semester', 'Ganjil');

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;

        $students = Siswa::where('kelas', $kelas)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $attQuery = AbsensiSiswa::whereIn('siswa_id', $students->pluck('id'));
        if ($targetYear?->tanggal_mulai && $targetYear?->tanggal_selesai) {
            $attQuery->whereBetween('tanggal', [
                \Carbon\Carbon::parse($targetYear->tanggal_mulai)->toDateString(),
                \Carbon\Carbon::parse($targetYear->tanggal_selesai)->toDateString(),
            ]);
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
        ]);

        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = $request->input('semester');
        $this->resolveAllowedHomeroomClasses($tahunId, $request->input('kelas'));
        $catatanList = $request->input('catatan', []);

        foreach ($catatanList as $siswaId => $text) {
            StudentStsSummary::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $tahunId,
                    'semester' => $semester,
                ],
                [
                    'catatan_wali_kelas' => trim($text),
                ]
            );
        }

        return redirect()->route('erapor.cetak.catatan-wali', [
            'kelas' => $request->input('kelas'),
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Catatan Wali Kelas berhasil disimpan dan akan langsung dicetak pada Lembar Rapor Siswa.');
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
        $this->resolveAllowedHomeroomClasses($tahunId, $siswa->kelas);

        $stsSummary = StudentStsSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->first();

        $att = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->selectRaw("
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
            $stsSummary?->avg_score,
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
        $classes = Siswa::select('kelas')->distinct()->whereNotNull('kelas')->orderBy('kelas')->pluck('kelas');

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
     * Print single student formal report card (A4 portrait).
     */
    public function printSiswa(Request $request, Siswa $siswa)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $tahunId = (int) $request->query('tahun_ajaran_id', $siswa->tahun_ajaran_id ?? $activeYear?->id ?? 1);
        $this->resolveAllowedHomeroomClasses($tahunId, $siswa->kelas);
        $semester = $request->query('semester', 'Ganjil');
        $jenisRapor = $request->query('jenis_rapor', 'sts');
        if ($jenisRapor === 'akhir') {
            $jenisRapor = 'uas';
        }

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $schoolSettings = PengaturanSekolah::first();

        // Fetch student's grades for all subjects
        $subjects = MataPelajaran::orderBy('nama', 'asc')->get();
        $summaries = SubjectGradeSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('mata_pelajaran_id');

        // Ensure class STS summary & rankings are up-to-date before printing
        if ($siswa->kelas) {
            app(\App\Services\GradingCalculationService::class)->recalculateClassSTS($siswa->kelas, $tahunId, $semester);
        }

        // Student overall STS / semester stats
        $stsSummary = StudentStsSummary::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->first();

        // Attendance stats (distinct days per status in the academic year)
        $attQuery = AbsensiSiswa::where('siswa_id', $siswa->id);
        if ($targetYear?->tanggal_mulai && $targetYear?->tanggal_selesai) {
            $attQuery->whereBetween('tanggal', [
                \Carbon\Carbon::parse($targetYear->tanggal_mulai)->toDateString(),
                \Carbon\Carbon::parse($targetYear->tanggal_selesai)->toDateString(),
            ]);
        }
        $attendance = $attQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        // Wali Kelas & Kepala Sekolah
        $raporSettings = RaporSetting::instance();
        $homeroom = ClassHomeroom::where('kelas', $siswa->kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first();

        $waliKelas = $homeroom?->guru ?? Jadwal::where('kelas', $siswa->kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first()?->guru;

        $kepalaSekolah = User::where('role', 'kepala_sekolah')->first();
        $tanggalRaporFormatted = $raporSettings->tanggal_rapor 
            ? $raporSettings->tanggal_rapor->translatedFormat('d F Y') 
            : date('d F Y');

        // Map subject teacher names for this class (with fallback to general subject teacher)
        $jadwalGuruMap = [];
        $classJadwals = Jadwal::where('kelas', $siswa->kelas)
            ->with('guru')
            ->orderByRaw('tahun_ajaran_id = ? DESC', [$tahunId])
            ->get();
        foreach ($classJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }
        $fallbackJadwals = Jadwal::with('guru')->orderBy('id', 'desc')->get();
        foreach ($fallbackJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }

        // Fetch individual assessment items & scores for this student (UH 1-3, Tugas 1-3, Praktek 1-2, Projek 1-2, Portofolio 1-2, STS)
        $assessmentItems = AssessmentItem::with('component')
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get();
        $studentScoresByItem = StudentAssessmentScore::where('siswa_id', $siswa->id)
            ->whereIn('assessment_item_id', $assessmentItems->pluck('id'))
            ->get()
            ->keyBy('assessment_item_id');
        $itemsBySubject = $assessmentItems->groupBy('mata_pelajaran_id');

        // Prepare grade items with competence descriptions & granular component scores
        $gradeItems = [];
        $totalNilai = 0;
        $filledCount = 0;

        foreach ($subjects as $mapel) {
            $sum = $summaries->get($mapel->id);
            $subjectItems = $itemsBySubject->get($mapel->id, collect());

            $uhScores = [1 => null, 2 => null, 3 => null];
            $tugasScores = [1 => null, 2 => null, 3 => null];
            $praktekScores = [1 => null, 2 => null];
            $projekScores = [1 => null, 2 => null];
            $portofolioScores = [1 => null, 2 => null];
            $stsItemScore = null;

            foreach ($subjectItems as $item) {
                $code = strtolower($item->component?->code ?? '');
                if ($code === '' && !empty($item->title)) {
                    $tLower = strtolower($item->title);
                    if (str_starts_with($tLower, 'tugas')) $code = 'tugas';
                    elseif (str_starts_with($tLower, 'uh') || str_contains($tLower, 'ulangan')) $code = 'uh';
                    elseif (str_starts_with($tLower, 'praktek') || str_starts_with($tLower, 'praktik')) $code = 'praktek';
                    elseif (str_starts_with($tLower, 'projek') || str_starts_with($tLower, 'proyek')) $code = 'projek';
                    elseif (str_starts_with($tLower, 'portofolio')) $code = 'portofolio';
                    elseif (str_starts_with($tLower, 'sts') || str_starts_with($tLower, 'pts')) $code = 'sts';
                }

                $seq = (int) $item->sequence;
                if ($seq <= 0 && preg_match('/\d+/', $item->title, $m)) {
                    $seq = (int) $m[0];
                }

                $val = $studentScoresByItem->get($item->id)?->score;
                if ($val === null) {
                    continue;
                }

                if ($code === 'uh') {
                    $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                    if ($targetSeq === null || $uhScores[$targetSeq] !== null) {
                        foreach ($uhScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $uhScores)) {
                        $uhScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'tugas') {
                    $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                    if ($targetSeq === null || $tugasScores[$targetSeq] !== null) {
                        foreach ($tugasScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $tugasScores)) {
                        $tugasScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'praktek') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $praktekScores[$targetSeq] !== null) {
                        foreach ($praktekScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $praktekScores)) {
                        $praktekScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'projek') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $projekScores[$targetSeq] !== null) {
                        foreach ($projekScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $projekScores)) {
                        $projekScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'portofolio') {
                    $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                    if ($targetSeq === null || $portofolioScores[$targetSeq] !== null) {
                        foreach ($portofolioScores as $k => $v) {
                            if ($v === null) { $targetSeq = $k; break; }
                        }
                    }
                    if ($targetSeq !== null && array_key_exists($targetSeq, $portofolioScores)) {
                        $portofolioScores[$targetSeq] = (float) $val;
                    }
                } elseif ($code === 'sts') {
                    $stsItemScore = (float) $val;
                }
            }

            // Fallback to summary averages in sequence 1 if no granular item score was recorded yet
            if (empty(array_filter($uhScores, fn($v) => $v !== null)) && $sum?->uh_avg !== null) {
                $uhScores[1] = (float) $sum->uh_avg;
            }
            if (empty(array_filter($tugasScores, fn($v) => $v !== null)) && $sum?->tugas_avg !== null) {
                $tugasScores[1] = (float) $sum->tugas_avg;
            }
            if (empty(array_filter($praktekScores, fn($v) => $v !== null)) && $sum?->praktek_avg !== null) {
                $praktekScores[1] = (float) $sum->praktek_avg;
            }
            if (empty(array_filter($projekScores, fn($v) => $v !== null)) && $sum?->projek_avg !== null) {
                $projekScores[1] = (float) $sum->projek_avg;
            }
            if (empty(array_filter($portofolioScores, fn($v) => $v !== null)) && $sum?->portofolio_avg !== null) {
                $portofolioScores[1] = (float) $sum->portofolio_avg;
            }

            $formatif = null;
            if ($sum) {
                $components = array_filter([$sum->uh_avg, $sum->tugas_avg, $sum->praktek_avg, $sum->projek_avg, $sum->portofolio_avg], fn($v) => $v !== null);
                if (!empty($components)) {
                    $formatif = round(array_sum($components) / count($components), 1);
                }
            }

            $sumatif = $stsItemScore ?? $sum?->sts_score;
            // Nilai STS / PTS murni untuk cetak Rapor STS (fallback ke final_score jika sts_score belum dipisah)
            $nilaiSts = $sumatif ?? $sum?->final_score ?? $formatif;
            // Nilai Akhir (UAS) menggabungkan komponen proses (Tugas, Portofolio, UH, Projek) dengan Sumatif/CBT
            $nilaiRapor = $sum?->final_score ?? $sumatif ?? $formatif;

            $acuanSkor = ($jenisRapor === 'sts') ? $nilaiSts : $nilaiRapor;

            if ($acuanSkor !== null) {
                $totalNilai += $acuanSkor;
                $filledCount++;
            }

            $predikat = self::calculatePredikat($acuanSkor);
            $deskripsi = !empty($sum?->capaian_kompetensi) 
                ? $sum->capaian_kompetensi 
                : self::generateDeskripsi($mapel->nama, $acuanSkor, $predikat);

            $gradeItems[] = [
                'nama' => $mapel->nama,
                'guru_nama' => $jadwalGuruMap[$mapel->id] ?? null,
                'kode' => $mapel->kode ?? '-',
                'kkm' => $homeroom ? $homeroom->getKkmForSubject($mapel->id, $mapel->kkm ?? $raporSettings->default_kkm ?? 75) : ($mapel->kkm !== null ? (float) $mapel->kkm : (float) ($raporSettings->default_kkm ?? 75)),
                'uh' => $uhScores,
                'tugas' => $tugasScores,
                'praktek' => $praktekScores,
                'projek' => $projekScores,
                'portofolio' => $portofolioScores,
                'formatif' => $formatif,
                'sumatif' => $sumatif,
                'nilai_sts' => $nilaiSts,
                'nilai_rapor' => $nilaiRapor,
                'predikat' => $predikat,
                'deskripsi' => $deskripsi,
            ];
        }

        $rataRata = $filledCount > 0 ? round($totalNilai / $filledCount, 1) : null;
        $totalSiswaKelas = Siswa::where('kelas', $siswa->kelas)->count();

        $classAvgRaw = StudentStsSummary::whereIn('siswa_id', Siswa::where('kelas', $siswa->kelas)->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->whereNotNull('average_score')
            ->avg('average_score');
        $rataRataKelas = $classAvgRaw !== null ? round((float) $classAvgRaw, 1) : $rataRata;
        $showKkm = (bool) ($raporSettings->show_kkm ?? false);

        return view('pages.erapor.cetak-rapor-print', compact(
            'siswa',
            'targetYear',
            'semester',
            'jenisRapor',
            'schoolSettings',
            'raporSettings',
            'showKkm',
            'homeroom',
            'tanggalRaporFormatted',
            'gradeItems',
            'totalNilai',
            'rataRata',
            'rataRataKelas',
            'stsSummary',
            'attendance',
            'waliKelas',
            'kepalaSekolah',
            'totalSiswaKelas'
        ));
    }

    /**
     * Bulk print all students report card in a class (A4 portrait with page-break).
     */
    public function printKelas(Request $request, $kelas)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $this->resolveAllowedHomeroomClasses($tahunId, $kelas);
        $semester = $request->query('semester', 'Ganjil');
        $jenisRapor = $request->query('jenis_rapor', 'sts');
        if ($jenisRapor === 'akhir') {
            $jenisRapor = 'uas';
        }

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $schoolSettings = PengaturanSekolah::first();
        $subjects = MataPelajaran::orderBy('nama', 'asc')->get();

        $students = Siswa::where('kelas', $kelas)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $allSummaries = SubjectGradeSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        // Ensure class STS summary & rankings are up-to-date before bulk printing
        app(\App\Services\GradingCalculationService::class)->recalculateClassSTS($kelas, $tahunId, $semester);

        $stsSummaries = StudentStsSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $raporSettings = RaporSetting::instance();
        $homeroom = ClassHomeroom::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first();

        $waliKelas = $homeroom?->guru ?? Jadwal::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunId)
            ->with('guru')
            ->first()?->guru;

        $kepalaSekolah = User::where('role', 'kepala_sekolah')->first();
        $tanggalRaporFormatted = $raporSettings->tanggal_rapor 
            ? $raporSettings->tanggal_rapor->translatedFormat('d F Y') 
            : date('d F Y');
        $totalSiswaKelas = $students->count();

        // Map subject teacher names for this class (with fallback)
        $jadwalGuruMap = [];
        $classJadwals = Jadwal::where('kelas', $kelas)
            ->with('guru')
            ->orderByRaw('tahun_ajaran_id = ? DESC', [$tahunId])
            ->get();
        foreach ($classJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }
        $fallbackJadwals = Jadwal::with('guru')->orderBy('id', 'desc')->get();
        foreach ($fallbackJadwals as $j) {
            if ($j->mata_pelajaran_id && $j->guru && !isset($jadwalGuruMap[$j->mata_pelajaran_id])) {
                $jadwalGuruMap[$j->mata_pelajaran_id] = $j->guru->nama_lengkap;
            }
        }

        // Fetch individual assessment items & scores for all students in this class
        $assessmentItems = AssessmentItem::with('component')
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get();
        $allStudentScores = StudentAssessmentScore::whereIn('siswa_id', $students->pluck('id'))
            ->whereIn('assessment_item_id', $assessmentItems->pluck('id'))
            ->get()
            ->groupBy('siswa_id');
        $itemsBySubject = $assessmentItems->groupBy('mata_pelajaran_id');

        $classAvgRaw = $stsSummaries->whereNotNull('average_score')->avg('average_score');
        $rataRataKelas = $classAvgRaw !== null ? round((float) $classAvgRaw, 1) : null;

        // Build data per student
        $raporData = [];
        foreach ($students as $siswa) {
            $studentSummaries = $allSummaries->get($siswa->id, collect())->keyBy('mata_pelajaran_id');
            $studentScoresByItem = $allStudentScores->get($siswa->id, collect())->keyBy('assessment_item_id');
            $sts = $stsSummaries->get($siswa->id);

            $attQuery = AbsensiSiswa::where('siswa_id', $siswa->id);
            if ($targetYear?->tanggal_mulai && $targetYear?->tanggal_selesai) {
                $attQuery->whereBetween('tanggal', [
                    \Carbon\Carbon::parse($targetYear->tanggal_mulai)->toDateString(),
                    \Carbon\Carbon::parse($targetYear->tanggal_selesai)->toDateString(),
                ]);
            }
            $attendance = $attQuery->selectRaw("
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                    COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
                ")
                ->first();

            $gradeItems = [];
            $totalNilai = 0;
            $filledCount = 0;

            foreach ($subjects as $mapel) {
                $sum = $studentSummaries->get($mapel->id);
                $subjectItems = $itemsBySubject->get($mapel->id, collect());

                $uhScores = [1 => null, 2 => null, 3 => null];
                $tugasScores = [1 => null, 2 => null, 3 => null];
                $praktekScores = [1 => null, 2 => null];
                $projekScores = [1 => null, 2 => null];
                $portofolioScores = [1 => null, 2 => null];
                $stsItemScore = null;

                foreach ($subjectItems as $item) {
                    $code = strtolower($item->component?->code ?? '');
                    if ($code === '' && !empty($item->title)) {
                        $tLower = strtolower($item->title);
                        if (str_starts_with($tLower, 'tugas')) $code = 'tugas';
                        elseif (str_starts_with($tLower, 'uh') || str_contains($tLower, 'ulangan')) $code = 'uh';
                        elseif (str_starts_with($tLower, 'praktek') || str_starts_with($tLower, 'praktik')) $code = 'praktek';
                        elseif (str_starts_with($tLower, 'projek') || str_starts_with($tLower, 'proyek')) $code = 'projek';
                        elseif (str_starts_with($tLower, 'portofolio')) $code = 'portofolio';
                        elseif (str_starts_with($tLower, 'sts') || str_starts_with($tLower, 'pts')) $code = 'sts';
                    }

                    $seq = (int) $item->sequence;
                    if ($seq <= 0 && preg_match('/\d+/', $item->title, $m)) {
                        $seq = (int) $m[0];
                    }

                    $val = $studentScoresByItem->get($item->id)?->score;
                    if ($val === null) {
                        continue;
                    }

                    if ($code === 'uh') {
                        $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                        if ($targetSeq === null || $uhScores[$targetSeq] !== null) {
                            foreach ($uhScores as $k => $v) {
                                if ($v === null) { $targetSeq = $k; break; }
                            }
                        }
                        if ($targetSeq !== null && array_key_exists($targetSeq, $uhScores)) {
                            $uhScores[$targetSeq] = (float) $val;
                        }
                    } elseif ($code === 'tugas') {
                        $targetSeq = ($seq >= 1 && $seq <= 3) ? $seq : null;
                        if ($targetSeq === null || $tugasScores[$targetSeq] !== null) {
                            foreach ($tugasScores as $k => $v) {
                                if ($v === null) { $targetSeq = $k; break; }
                            }
                        }
                        if ($targetSeq !== null && array_key_exists($targetSeq, $tugasScores)) {
                            $tugasScores[$targetSeq] = (float) $val;
                        }
                    } elseif ($code === 'praktek') {
                        $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                        if ($targetSeq === null || $praktekScores[$targetSeq] !== null) {
                            foreach ($praktekScores as $k => $v) {
                                if ($v === null) { $targetSeq = $k; break; }
                            }
                        }
                        if ($targetSeq !== null && array_key_exists($targetSeq, $praktekScores)) {
                            $praktekScores[$targetSeq] = (float) $val;
                        }
                    } elseif ($code === 'projek') {
                        $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                        if ($targetSeq === null || $projekScores[$targetSeq] !== null) {
                            foreach ($projekScores as $k => $v) {
                                if ($v === null) { $targetSeq = $k; break; }
                            }
                        }
                        if ($targetSeq !== null && array_key_exists($targetSeq, $projekScores)) {
                            $projekScores[$targetSeq] = (float) $val;
                        }
                    } elseif ($code === 'portofolio') {
                        $targetSeq = ($seq >= 1 && $seq <= 2) ? $seq : null;
                        if ($targetSeq === null || $portofolioScores[$targetSeq] !== null) {
                            foreach ($portofolioScores as $k => $v) {
                                if ($v === null) { $targetSeq = $k; break; }
                            }
                        }
                        if ($targetSeq !== null && array_key_exists($targetSeq, $portofolioScores)) {
                            $portofolioScores[$targetSeq] = (float) $val;
                        }
                    } elseif ($code === 'sts') {
                        $stsItemScore = (float) $val;
                    }
                }

                if (empty(array_filter($uhScores, fn($v) => $v !== null)) && $sum?->uh_avg !== null) {
                    $uhScores[1] = (float) $sum->uh_avg;
                }
                if (empty(array_filter($tugasScores, fn($v) => $v !== null)) && $sum?->tugas_avg !== null) {
                    $tugasScores[1] = (float) $sum->tugas_avg;
                }
                if (empty(array_filter($praktekScores, fn($v) => $v !== null)) && $sum?->praktek_avg !== null) {
                    $praktekScores[1] = (float) $sum->praktek_avg;
                }
                if (empty(array_filter($projekScores, fn($v) => $v !== null)) && $sum?->projek_avg !== null) {
                    $projekScores[1] = (float) $sum->projek_avg;
                }
                if (empty(array_filter($portofolioScores, fn($v) => $v !== null)) && $sum?->portofolio_avg !== null) {
                    $portofolioScores[1] = (float) $sum->portofolio_avg;
                }

                $formatif = null;
                if ($sum) {
                    $components = array_filter([$sum->uh_avg, $sum->tugas_avg, $sum->praktek_avg, $sum->projek_avg, $sum->portofolio_avg], fn($v) => $v !== null);
                    if (!empty($components)) {
                        $formatif = round(array_sum($components) / count($components), 1);
                    }
                }

                $sumatif = $stsItemScore ?? $sum?->sts_score;
                // Nilai STS murni untuk cetak Rapor STS
                $nilaiSts = $sumatif ?? $sum?->final_score ?? $formatif;
                // Nilai Akhir (UAS) menggabungkan komponen proses (Tugas, Portofolio, UH, Projek) dengan Sumatif/CBT
                $nilaiRapor = $sum?->final_score ?? $sumatif ?? $formatif;

                $acuanSkor = ($jenisRapor === 'sts') ? $nilaiSts : $nilaiRapor;

                if ($acuanSkor !== null) {
                    $totalNilai += $acuanSkor;
                    $filledCount++;
                }

                $predikat = self::calculatePredikat($acuanSkor);
                $deskripsi = !empty($sum?->capaian_kompetensi)
                    ? $sum->capaian_kompetensi
                    : self::generateDeskripsi($mapel->nama, $acuanSkor, $predikat);

                $gradeItems[] = [
                    'nama' => $mapel->nama,
                    'guru_nama' => $jadwalGuruMap[$mapel->id] ?? null,
                    'kode' => $mapel->kode ?? '-',
                    'kkm' => $homeroom ? $homeroom->getKkmForSubject($mapel->id, $mapel->kkm ?? $raporSettings->default_kkm ?? 75) : ($mapel->kkm !== null ? (float) $mapel->kkm : (float) ($raporSettings->default_kkm ?? 75)),
                    'uh' => $uhScores,
                    'tugas' => $tugasScores,
                    'praktek' => $praktekScores,
                    'projek' => $projekScores,
                    'portofolio' => $portofolioScores,
                    'formatif' => $formatif,
                    'sumatif' => $sumatif,
                    'nilai_sts' => $nilaiSts,
                    'nilai_rapor' => $nilaiRapor,
                    'predikat' => $predikat,
                    'deskripsi' => $deskripsi,
                ];
            }

            $rataRata = $filledCount > 0 ? round($totalNilai / $filledCount, 1) : null;

            $raporData[] = [
                'siswa' => $siswa,
                'gradeItems' => $gradeItems,
                'totalNilai' => $totalNilai,
                'rataRata' => $rataRata,
                'rataRataKelas' => $rataRataKelas ?? $rataRata,
                'stsSummary' => $sts,
                'attendance' => $attendance,
            ];
        }

        $showKkm = (bool) ($raporSettings->show_kkm ?? false);

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
            'A' => "Menunjukkan penguasaan materi {$mapelNama} yang sangat baik dan melampaui kriteria ketercapaian tujuan pembelajaran.",
            'B' => "Menunjukkan penguasaan materi {$mapelNama} yang baik dan telah mencapai tujuan pembelajaran yang ditetapkan.",
            'C' => "Cukup mampu menguasai materi {$mapelNama}, disarankan untuk terus berlatih dan memperdalam pemahaman.",
            'D' => "Perlu bimbingan dan pendampingan intensif dalam mencapai tujuan pembelajaran materi {$mapelNama}.",
            default => "Capaian pembelajaran materi {$mapelNama} memenuhi standar minimal.",
        };
    }
}
