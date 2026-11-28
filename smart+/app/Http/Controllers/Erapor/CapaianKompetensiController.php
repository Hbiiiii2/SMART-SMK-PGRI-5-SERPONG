<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporArchive;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Services\RaporArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CapaianKompetensiController extends Controller
{
    /**
     * Roles with school-wide access to all classes and subjects.
     */
    protected function isPrivilegedRole(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'admin_sekolah', 'kepala_sekolah'], true);
    }

    /**
     * Resolve schedules belonging to the authenticated teacher.
     */
    protected function resolveTeacherSchedules(?int $tahunId): Collection
    {
        $guru = auth()->user()?->guru;
        if (!$guru) {
            return collect();
        }

        $base = Jadwal::where('guru_id', $guru->id)
            ->whereNotNull('mata_pelajaran_id')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '');

        if ($tahunId) {
            $scoped = (clone $base)->where('tahun_ajaran_id', $tahunId)->get();
            if ($scoped->isNotEmpty()) {
                return $scoped;
            }

            if (Jadwal::where('tahun_ajaran_id', $tahunId)->doesntExist()) {
                return (clone $base)->get();
            }

            return collect();
        }

        return $base->get();
    }

    /**
     * Enforce that non-privileged teachers can only view/edit classes & subjects on their Jadwal.
     */
    protected function assertTeacherCanAccess(?int $tahunId, string $kelas, int $mapelId): void
    {
        if ($this->isPrivilegedRole()) {
            return;
        }

        $schedules = $this->resolveTeacherSchedules($tahunId);
        $allowed = $schedules->contains(
            fn ($j) => $j->kelas === $kelas && (int) $j->mata_pelajaran_id === $mapelId
        );

        if (!$allowed) {
            abort(403, 'Anda tidak memiliki jadwal mengajar pada kelas dan mata pelajaran tersebut.');
        }
    }

    /**
     * Refresh existing active-year report card snapshots for active students (alumni/past years stay immutable).
     */
    protected function syncActiveRaporArchives(int $tahunId, string $semester, Collection $studentIds): void
    {
        $targetYear = TahunAjaran::find($tahunId);
        if (!$targetYear?->is_aktif || $studentIds->isEmpty()) {
            return;
        }

        $activeStudents = Siswa::whereIn('id', $studentIds)
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })
            ->get()
            ->keyBy('id');

        if ($activeStudents->isEmpty()) {
            return;
        }

        $existingArchives = RaporArchive::whereIn('siswa_id', $activeStudents->keys())
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get();

        if ($existingArchives->isEmpty()) {
            return;
        }

        $archiveService = app(RaporArchiveService::class);
        foreach ($existingArchives as $arch) {
            $stModel = $activeStudents->get($arch->siswa_id);
            if ($stModel) {
                $archiveService->archiveStudent(
                    $stModel,
                    $tahunId,
                    $semester,
                    $arch->jenis_rapor,
                    auth()->id(),
                    recalculate: false
                );
            }
        }
    }

    /**
     * Display interface for subject teachers to input and customize competence descriptions.
     */
    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $semester = $request->query('semester', 'Ganjil');
        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $schoolName = PengaturanSekolah::first()?->nama_sekolah ?? 'SMK PGRI 5 Serpong';
        $isGuru = !$this->isPrivilegedRole();

        if ($isGuru) {
            $schedules = $this->resolveTeacherSchedules($tahunId);
            $allowedByClass = $schedules->groupBy('kelas')->map(
                fn ($rows) => $rows->pluck('mata_pelajaran_id')->map(fn ($id) => (int) $id)->unique()->values()
            );
            $classes = $allowedByClass->keys()->sort()->values();

            if ($classes->isEmpty()) {
                if ($request->filled('kelas') || $request->filled('mata_pelajaran_id')) {
                    abort(403, 'Anda belum memiliki jadwal mengajar pada kelas atau mata pelajaran tersebut.');
                }

                $kelas = '';
                $subjects = collect();
                $targetMapel = null;
            } else {
                if ($request->filled('kelas')) {
                    $kelas = (string) $request->query('kelas');
                    if (!$classes->contains($kelas)) {
                        abort(403, 'Anda tidak memiliki jadwal mengajar pada kelas tersebut.');
                    }
                } else {
                    $kelas = (string) $classes->first();
                }

                $allowedMapelIds = $allowedByClass->get($kelas, collect());
                $subjects = MataPelajaran::sortSubjects(MataPelajaran::whereIn('id', $allowedMapelIds)->get());

                if ($request->filled('mata_pelajaran_id')) {
                    $requestedMapelId = (int) $request->query('mata_pelajaran_id');
                    if (!$allowedMapelIds->contains($requestedMapelId)) {
                        abort(403, 'Anda tidak memiliki jadwal mengajar untuk mata pelajaran tersebut pada kelas ' . $kelas . '.');
                    }
                    $mapelId = $requestedMapelId;
                } else {
                    $mapelId = (int) ($subjects->first()?->id ?? 0);
                }

                $targetMapel = $subjects->firstWhere('id', $mapelId);
            }
        } else {
            $classes = Rombel::classNames($tahunId);
            $kelas = (string) $request->query('kelas', $classes->first() ?? '');
            $subjects = $kelas
                ? MataPelajaran::forClass($kelas, $tahunId, $semester)
                : MataPelajaran::sortSubjects(MataPelajaran::all());

            $requestedMapelId = $request->filled('mata_pelajaran_id') ? (int) $request->query('mata_pelajaran_id') : null;
            $targetMapel = ($requestedMapelId ? $subjects->firstWhere('id', $requestedMapelId) : null) ?? $subjects->first();
        }

        $students = ($kelas !== '' && $targetMapel)
            ? Siswa::where('kelas', $kelas)->orderBy('nama_lengkap', 'asc')->get()
            : collect();

        $summaries = ($students->isNotEmpty() && $targetMapel)
            ? SubjectGradeSummary::whereIn('siswa_id', $students->pluck('id'))
                ->where('mata_pelajaran_id', $targetMapel->id)
                ->where('tahun_ajaran_id', $tahunId)
                ->where('semester', $semester)
                ->get()
                ->keyBy('siswa_id')
            : collect();

        return view('pages.erapor.capaian-kompetensi', compact(
            'subjects',
            'targetMapel',
            'classes',
            'kelas',
            'allYears',
            'targetYear',
            'semester',
            'students',
            'summaries',
            'schoolName',
            'isGuru'
        ));
    }

    /**
     * Save competence descriptions per student for a specific subject.
     */
    public function save(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'kelas' => 'required|string',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'capaian' => 'array',
        ]);

        $mapelId = (int) $request->input('mata_pelajaran_id');
        $kelas = (string) $request->input('kelas');
        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = (string) $request->input('semester');

        $this->assertTeacherCanAccess($tahunId, $kelas, $mapelId);

        $capaianList = $request->input('capaian', []);
        $validStudentIds = Siswa::where('kelas', $kelas)->pluck('id')->flip();
        $updatedStudentIds = [];

        foreach ($capaianList as $siswaId => $text) {
            $sid = (int) $siswaId;
            if (!$validStudentIds->has($sid)) {
                continue;
            }

            SubjectGradeSummary::updateOrCreate(
                [
                    'siswa_id' => $sid,
                    'mata_pelajaran_id' => $mapelId,
                    'tahun_ajaran_id' => $tahunId,
                    'semester' => $semester,
                ],
                [
                    'kelas' => $kelas,
                    'capaian_kompetensi' => trim((string) $text),
                ]
            );
            $updatedStudentIds[] = $sid;
        }

        $this->syncActiveRaporArchives($tahunId, $semester, collect($updatedStudentIds));

        return redirect()->route('erapor.capaian.index', [
            'mata_pelajaran_id' => $mapelId,
            'kelas' => $kelas,
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Deskripsi Capaian Kompetensi berhasil disimpan dan siap dicetak pada Rapor.');
    }

    /**
     * Auto-generate Kurikulum Merdeka competence descriptors for all students in the class based on their scores.
     */
    public function autoGenerate(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'kelas' => 'required|string',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $mapelId = (int) $request->input('mata_pelajaran_id');
        $kelas = (string) $request->input('kelas');
        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = (string) $request->input('semester');

        $this->assertTeacherCanAccess($tahunId, $kelas, $mapelId);

        $mapel = MataPelajaran::findOrFail($mapelId);
        $students = Siswa::where('kelas', $kelas)->get();

        foreach ($students as $siswa) {
            $sum = SubjectGradeSummary::firstOrNew([
                'siswa_id' => $siswa->id,
                'mata_pelajaran_id' => $mapelId,
                'tahun_ajaran_id' => $tahunId,
                'semester' => $semester,
            ]);

            $formatif = null;
            $components = array_filter([$sum->uh_avg, $sum->tugas_avg, $sum->praktek_avg, $sum->projek_avg], fn($v) => $v !== null);
            if (!empty($components)) {
                $formatif = round(array_sum($components) / count($components), 1);
            }

            $score = $sum->final_score ?? $sum->asat_score ?? $sum->sts_score ?? $formatif;
            $predikat = CetakRaporController::calculatePredikat($score);
            $sum->kelas = $kelas;
            $sum->capaian_kompetensi = CetakRaporController::generateDeskripsi($mapel->nama, $score, $predikat);
            $sum->save();
        }

        $this->syncActiveRaporArchives($tahunId, $semester, $students->pluck('id'));

        return redirect()->route('erapor.capaian.index', [
            'mata_pelajaran_id' => $mapelId,
            'kelas' => $kelas,
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Seluruh Capaian Kompetensi Kurikulum Merdeka berhasil digenerate otomatis.');
    }
}
