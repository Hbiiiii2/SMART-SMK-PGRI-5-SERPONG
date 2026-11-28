<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SiswaDapodikExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaRequest;
use App\Models\DapodikSyncLog;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaOrangTua;
use App\Models\SiswaPeriodik;
use App\Models\SiswaProfil;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\CacheService;
use App\Services\DapodikApiService;
use App\Services\DapodikImportService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $siswas = Siswa::with(['user', 'tahunAjaran', 'rombel', 'profil', 'orangTuas'])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('nama_lengkap', 'like', "%{$s}%")
                        ->orWhere('nisn', 'like', "%{$s}%")
                        ->orWhere('nipd', 'like', "%{$s}%")
                        ->orWhere('nik', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('rombel_id'), fn ($q) => $q->where('rombel_id', $request->rombel_id))
            ->when($request->filled('kelas'), fn ($q) => $q->where('kelas', $request->kelas))
            ->when($request->filled('status_siswa'), fn ($q) => $q->where('status_siswa', $request->status_siswa))
            ->when($request->filled('tahun_ajaran_id'), fn ($q) => $q->where('tahun_ajaran_id', $request->tahun_ajaran_id))
            ->orderBy('kelas')
            ->orderBy('nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $rombels = Rombel::where('is_aktif', true)->orderBy('tingkat')->orderBy('nama')->get();
        $kelases = Rombel::classNames(null, true, false);

        $totalSiswa = Siswa::count();
        $aktifSiswa = Siswa::aktif()->count();
        $nonAktifSiswa = max(0, $totalSiswa - $aktifSiswa);

        return view('pages.admin.siswa.index', compact(
            'siswas',
            'tahunAjarans',
            'rombels',
            'kelases',
            'totalSiswa',
            'aktifSiswa',
            'nonAktifSiswa'
        ));
    }

    public function create()
    {
        return view('pages.admin.siswa.create', array_merge([
            'title' => 'Tambah Siswa',
            'siswa' => new Siswa(['status_siswa' => 'aktif']),
        ], $this->formOptions()));
    }

    public function store(SiswaRequest $request)
    {
        CacheService::flushDropdowns();

        $siswa = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $rombel = $this->resolveRombelForSave($validated);

            $userId = null;
            if (!empty($validated['email'])) {
                $user = User::create([
                    'name' => $validated['nama_lengkap'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'siswa',
                    'is_active' => ($validated['status_siswa'] ?? 'aktif') === 'aktif',
                ]);
                $userId = $user->id;
            }

            $coreData = $this->extractCoreSiswaData($validated, $rombel, $userId);
            if ($request->hasFile('foto')) {
                $coreData['foto'] = $request->file('foto')->store('foto/siswa', 'public');
            }

            $siswa = Siswa::create($coreData);
            $this->syncRelatedDapodikData($siswa, $request, $rombel);

            return $siswa;
        });

        $this->logActivity('create_siswa', 'Menambah siswa: ' . $siswa->nama_lengkap, $request);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        $siswa->load([
            'user',
            'rombel.waliKelas',
            'profil',
            'orangTuas',
            'periodiks.tahunAjaran',
            'riwayatKelas.tahunAjaran',
            'rfidCards',
            'absensiSiswas.jadwal.mataPelajaran',
            'tahunAjaran',
        ]);

        return view('pages.admin.siswa.show', [
            'title' => 'Profil Repository Siswa - ' . $siswa->nama_lengkap,
            'siswa' => $siswa,
        ]);
    }

    public function edit(Siswa $siswa)
    {
        $siswa->load(['user', 'rombel', 'profil', 'orangTuas', 'latestPeriodik']);

        return view('pages.admin.siswa.edit', array_merge([
            'title' => 'Edit Siswa',
            'siswa' => $siswa,
        ], $this->formOptions()));
    }

    public function update(SiswaRequest $request, Siswa $siswa)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($request, $siswa) {
            $validated = $request->validated();
            $rombel = $this->resolveRombelForSave($validated);
            $userId = $siswa->user_id;

            if ($siswa->user) {
                $userUpdates = [
                    'name' => $validated['nama_lengkap'],
                    'is_active' => ($validated['status_siswa'] ?? $siswa->status_siswa ?? 'aktif') === 'aktif',
                ];
                if (!empty($validated['email'])) {
                    $userUpdates['email'] = $validated['email'];
                }
                if ($request->filled('password')) {
                    $userUpdates['password'] = Hash::make($validated['password']);
                }
                $siswa->user->update($userUpdates);
            } elseif (!empty($validated['email']) && $request->filled('password')) {
                $user = User::create([
                    'name' => $validated['nama_lengkap'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'siswa',
                    'is_active' => ($validated['status_siswa'] ?? 'aktif') === 'aktif',
                ]);
                $userId = $user->id;
            }

            $coreData = $this->extractCoreSiswaData($validated, $rombel, $userId);
            if ($request->hasFile('foto')) {
                if ($siswa->foto) {
                    Storage::disk('public')->delete($siswa->foto);
                }
                $coreData['foto'] = $request->file('foto')->store('foto/siswa', 'public');
            }

            $siswa->update($coreData);
            $this->syncRelatedDapodikData($siswa, $request, $rombel);
        });

        $this->logActivity('update_siswa', 'Mengubah data siswa: ' . $siswa->nama_lengkap, $request);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        CacheService::flushDropdowns();

        DB::transaction(function () use ($siswa) {
            if ($siswa->foto) {
                Storage::disk('public')->delete($siswa->foto);
            }
            $user = $siswa->user;
            $siswa->delete();
            if ($user) {
                $user->delete();
            }
        });

        $this->logActivity('delete_siswa', 'Menghapus siswa: ' . $siswa->nama_lengkap);

        return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    public function kenaikanKelas()
    {
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $kelases = Rombel::classNames(null, true, true);
        $siswasByKelas = Siswa::aktif()
            ->with('tahunAjaran')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->orderBy('nama_lengkap')
            ->get()
            ->groupBy('kelas');

        return view('pages.admin.siswa.kenaikan-kelas', compact('tahunAjarans', 'kelases', 'siswasByKelas'));
    }

    public function prosesKenaikanKelas(Request $request)
    {
        $request->validate([
            'tahun_ajaran_tujuan' => 'required|exists:tahun_ajaran,id',
            'kelas_tujuan' => 'nullable|array',
            'siswa_naik' => 'nullable|array',
            'tahun_lulus' => 'nullable|integer|min:2000|max:2100',
            'tanggal_kelulusan' => 'nullable|date',
        ]);

        $tahunAjaranTujuanId = (int) $request->tahun_ajaran_tujuan;
        $kelasTujuanMap = $request->kelas_tujuan ?? [];
        $siswaNaikMap = $request->siswa_naik ?? [];
        $tahunLulusInput = $request->filled('tahun_lulus') ? (int) $request->input('tahun_lulus') : null;
        $tanggalKelulusanInput = $request->input('tanggal_kelulusan');

        DB::transaction(function () use ($tahunAjaranTujuanId, $kelasTujuanMap, $siswaNaikMap, $tahunLulusInput, $tanggalKelulusanInput) {
            foreach ($kelasTujuanMap as $kelasAsal => $kelasTujuan) {
                if (empty($kelasTujuan)) {
                    continue;
                }

                $semuaSiswaDiKelas = Siswa::where('kelas', $kelasAsal)->get();
                $siswaNaikIds = $siswaNaikMap[$kelasAsal] ?? [];

                $siswaNaik = $semuaSiswaDiKelas->whereIn('id', $siswaNaikIds);
                $siswaTinggal = $semuaSiswaDiKelas->whereNotIn('id', $siswaNaikIds);

                $isGraduating = in_array(strtolower(trim($kelasTujuan)), ['alumni', 'lulus'], true);
                $targetRombel = $isGraduating ? null : Rombel::resolveOrCreateByName($kelasTujuan, $tahunAjaranTujuanId, 'Ganjil');

                if ($siswaNaik->isNotEmpty()) {
                    $gradYearId = null;
                    $resolvedTahunLulus = $tahunLulusInput;

                    if ($isGraduating) {
                        $sampleYearId = $siswaNaik->first()->tahun_ajaran_id ?: TahunAjaran::where('is_aktif', true)->value('id') ?: $tahunAjaranTujuanId;
                        $gradYearId = (int) $sampleYearId;
                        if ($resolvedTahunLulus === null) {
                            $taNama = (string) TahunAjaran::where('id', $gradYearId)->value('nama');
                            if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', $taNama, $m)) {
                                $resolvedTahunLulus = (int) $m[2];
                            } else {
                                $resolvedTahunLulus = (int) date('Y');
                            }
                        }
                    }

                    foreach ($siswaNaik as $sn) {
                        $prevYearId = $sn->tahun_ajaran_id ?? $tahunAjaranTujuanId;
                        RiwayatKelasSiswa::updateOrCreate(
                            [
                                'siswa_id' => $sn->id,
                                'tahun_ajaran_id' => $prevYearId,
                                'semester' => 'Genap',
                            ],
                            [
                                'rombel_id' => $sn->rombel_id,
                                'kelas' => $kelasAsal,
                                'status' => $isGraduating ? 'lulus' : 'naik_kelas',
                                'catatan' => $isGraduating
                                    ? "Lulus Angkatan {$resolvedTahunLulus} dari {$kelasAsal}"
                                    : "Naik ke {$kelasTujuan}",
                            ]
                        );

                        if (!$isGraduating) {
                            RiwayatKelasSiswa::updateOrCreate(
                                [
                                    'siswa_id' => $sn->id,
                                    'tahun_ajaran_id' => $tahunAjaranTujuanId,
                                    'semester' => 'Ganjil',
                                ],
                                [
                                    'rombel_id' => $targetRombel?->id,
                                    'kelas' => $kelasTujuan,
                                    'status' => 'aktif',
                                    'catatan' => "Tahun Ajaran Baru di {$kelasTujuan}",
                                ]
                            );
                        }
                    }

                    $updateData = [
                        'kelas' => $kelasTujuan,
                        'rombel_id' => $targetRombel?->id,
                        'tahun_ajaran_id' => $tahunAjaranTujuanId,
                        'status_siswa' => $isGraduating ? 'lulus' : 'aktif',
                    ];

                    if ($isGraduating) {
                        $updateData['tahun_lulus'] = $resolvedTahunLulus;
                        $updateData['tahun_ajaran_lulus_id'] = $gradYearId;
                        $updateData['tanggal_keluar'] = $tanggalKelulusanInput ?: now()->toDateString();
                        $updateData['alasan_keluar'] = "Lulus Angkatan {$resolvedTahunLulus}";
                    }

                    Siswa::whereIn('id', $siswaNaik->pluck('id'))->update($updateData);

                    if ($isGraduating) {
                        $userIds = $siswaNaik->pluck('user_id')->filter()->toArray();
                        if (!empty($userIds)) {
                            User::whereIn('id', $userIds)->update(['is_active' => false]);
                        }
                    }
                }

                if ($siswaTinggal->isNotEmpty()) {
                    $stayRombel = Rombel::resolveOrCreateByName($kelasAsal, $tahunAjaranTujuanId, 'Ganjil');
                    foreach ($siswaTinggal as $st) {
                        RiwayatKelasSiswa::updateOrCreate(
                            [
                                'siswa_id' => $st->id,
                                'tahun_ajaran_id' => $tahunAjaranTujuanId,
                                'semester' => 'Ganjil',
                            ],
                            [
                                'rombel_id' => $stayRombel->id,
                                'kelas' => $kelasAsal,
                                'status' => 'tinggal_kelas',
                                'catatan' => "Tinggal di kelas {$kelasAsal}",
                            ]
                        );
                    }

                    Siswa::whereIn('id', $siswaTinggal->pluck('id'))->update([
                        'rombel_id' => $stayRombel->id,
                        'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    ]);
                }
            }
        });

        CacheService::flushDropdowns();
        $this->logActivity('kenaikan_kelas_massal', 'Memproses kenaikan kelas massal');

        return redirect()->route('admin.siswa.index')->with('success', 'Kenaikan kelas massal berhasil diproses.');
    }

    // --- Dapodik Import, Export & API Sync Hub ---

    public function dapodikIndex(DapodikApiService $apiService)
    {
        $tahunAjarans = TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        $rombels = Rombel::where('is_aktif', true)->orderBy('nama')->get();
        $syncLogs = DapodikSyncLog::with('user')->latest()->limit(15)->get();

        // Data Quality Audit sample: find students with missing NISN/NIPD/NIK/Ibu
        $allStudents = Siswa::aktif()->with(['profil', 'orangTuas'])->orderBy('kelas')->orderBy('nama_lengkap')->get();
        $qualityIssues = $allStudents
            ->map(function (Siswa $s) {
                $comp = $s->data_completeness;
                return [
                    'siswa' => $s,
                    'score' => $comp['score'],
                    'issues' => $comp['issues'],
                ];
            })
            ->filter(fn ($row) => $row['score'] < 100)
            ->sortBy('score')
            ->take(25)
            ->values();

        $avgCompleteness = $allStudents->isNotEmpty()
            ? (int) round($allStudents->avg(fn ($s) => $s->data_completeness['score']))
            : 100;

        return view('pages.admin.siswa.dapodik', [
            'title' => 'Hub Dapodik & Repository Siswa',
            'tahunAjarans' => $tahunAjarans,
            'rombels' => $rombels,
            'syncLogs' => $syncLogs,
            'qualityIssues' => $qualityIssues,
            'avgCompleteness' => $avgCompleteness,
            'totalActiveStudents' => $allStudents->count(),
            'apiConfigured' => $apiService->isConfigured(),
            'previewData' => session('dapodik_preview'),
        ]);
    }

    public function dapodikPreview(Request $request, DapodikImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        $preview = $importService->preview(
            $request->file('file'),
            (int) $request->tahun_ajaran_id,
            $request->semester
        );

        return redirect()->route('admin.siswa.dapodik.index')
            ->with('dapodik_preview', $preview);
    }

    public function dapodikImport(Request $request, DapodikImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|in:Ganjil,Genap',
            'create_accounts' => 'nullable|boolean',
            'default_password' => 'nullable|string|min:6|max:50',
        ]);

        $log = $importService->import(
            file: $request->file('file'),
            tahunAjaranId: (int) $request->tahun_ajaran_id,
            semester: $request->semester,
            createAccounts: $request->boolean('create_accounts'),
            defaultPassword: $request->input('default_password'),
            userId: auth()->id()
        );

        $this->logActivity('import_dapodik_siswa', $log->summary_notes, $request);

        return redirect()->route('admin.siswa.dapodik.index')
            ->with($log->status === 'failed' ? 'error' : 'success', $log->summary_notes);
    }

    public function dapodikExport(Request $request)
    {
        $rombelId = $request->filled('rombel_id') ? (int) $request->rombel_id : null;
        $statusSiswa = $request->filled('status_siswa') ? $request->string('status_siswa')->toString() : null;
        $tahunAjaranId = $request->filled('tahun_ajaran_id') ? (int) $request->tahun_ajaran_id : null;

        $filename = 'Repository_Siswa_Dapodik_' . now()->format('Ymd_His') . '.xlsx';
        $this->logActivity('export_dapodik_siswa', "Ekspor repository siswa: {$filename}", $request);

        return Excel::download(
            new SiswaDapodikExport($rombelId, $statusSiswa, $tahunAjaranId),
            $filename
        );
    }

    public function dapodikTestApi(DapodikApiService $apiService)
    {
        $result = $apiService->testConnection();

        return redirect()->route('admin.siswa.dapodik.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function dapodikSyncApi(Request $request, DapodikApiService $apiService)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajaran,id',
            'semester' => 'nullable|in:Ganjil,Genap',
            'create_accounts' => 'nullable|boolean',
        ]);

        $log = $apiService->syncFromApi(
            tahunAjaranId: $request->filled('tahun_ajaran_id') ? (int) $request->tahun_ajaran_id : null,
            semester: $request->input('semester', 'Ganjil'),
            createAccounts: $request->boolean('create_accounts'),
            userId: auth()->id()
        );

        $this->logActivity('sync_dapodik_api', $log->summary_notes, $request);

        return redirect()->route('admin.siswa.dapodik.index')
            ->with($log->status === 'failed' ? 'error' : 'success', $log->summary_notes);
    }

    protected function resolveRombelForSave(array $validated): ?Rombel
    {
        if (!empty($validated['rombel_id'])) {
            return Rombel::find($validated['rombel_id']);
        }

        if (!empty($validated['kelas'])) {
            $status = $validated['status_siswa'] ?? 'aktif';
            if ($status === 'aktif') {
                return Rombel::resolveOrCreateByName($validated['kelas'], (int) $validated['tahun_ajaran_id']);
            }
        }

        return null;
    }

    protected function extractCoreSiswaData(array $validated, ?Rombel $rombel, ?int $userId): array
    {
        return [
            'user_id' => $userId,
            'dapodik_peserta_didik_id' => $validated['dapodik_peserta_didik_id'] ?? null,
            'dapodik_registrasi_id' => $validated['dapodik_registrasi_id'] ?? null,
            'nipd' => $validated['nipd'] ?? null,
            'nisn' => $validated['nisn'] ?? null,
            'nik' => $validated['nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'nama_lengkap' => $validated['nama_lengkap'],
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'tempat_lahir' => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'agama' => $validated['agama'] ?? null,
            'kelas' => $rombel?->nama ?? ($validated['kelas'] ?? null),
            'rombel_id' => $rombel?->id,
            'alamat' => $validated['alamat'] ?? null,
            'no_telepon' => $validated['no_telepon'] ?? null,
            'telepon_rumah' => $validated['telepon_rumah'] ?? null,
            'no_wa_aktif' => $validated['no_wa_aktif'] ?? null,
            'email_pribadi' => $validated['email_pribadi'] ?? null,
            'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
            'status_siswa' => $validated['status_siswa'] ?? 'aktif',
            'tanggal_masuk' => $validated['tanggal_masuk'] ?? null,
            'tanggal_keluar' => $validated['tanggal_keluar'] ?? null,
            'alasan_keluar' => $validated['alasan_keluar'] ?? null,
        ];
    }

    protected function syncRelatedDapodikData(Siswa $siswa, SiswaRequest $request, ?Rombel $rombel): void
    {
        $profilInput = $request->input('profil', []);
        if (!empty($profilInput) || $siswa->profil()->exists()) {
            $profilData = [
                'alamat_jalan' => $profilInput['alamat_jalan'] ?? null,
                'rt' => $profilInput['rt'] ?? null,
                'rw' => $profilInput['rw'] ?? null,
                'nama_dusun' => $profilInput['nama_dusun'] ?? null,
                'kelurahan' => $profilInput['kelurahan'] ?? null,
                'kecamatan' => $profilInput['kecamatan'] ?? null,
                'kode_pos' => $profilInput['kode_pos'] ?? null,
                'jenis_tinggal' => $profilInput['jenis_tinggal'] ?? null,
                'alat_transportasi' => $profilInput['alat_transportasi'] ?? null,
                'lintang' => isset($profilInput['lintang']) && $profilInput['lintang'] !== '' ? (float) $profilInput['lintang'] : null,
                'bujur' => isset($profilInput['bujur']) && $profilInput['bujur'] !== '' ? (float) $profilInput['bujur'] : null,
                'no_registrasi_akta_lahir' => $profilInput['no_registrasi_akta_lahir'] ?? null,
                'skhun' => $profilInput['skhun'] ?? null,
                'no_peserta_un' => $profilInput['no_peserta_un'] ?? null,
                'no_seri_ijazah' => $profilInput['no_seri_ijazah'] ?? null,
                'sekolah_asal' => $profilInput['sekolah_asal'] ?? null,
                'kebutuhan_khusus' => $profilInput['kebutuhan_khusus'] ?? null,
                'anak_keberapa' => isset($profilInput['anak_keberapa']) && $profilInput['anak_keberapa'] !== '' ? (int) $profilInput['anak_keberapa'] : null,
                'penerima_kps' => !empty($profilInput['penerima_kps']),
                'no_kps' => $profilInput['no_kps'] ?? null,
                'penerima_kip' => !empty($profilInput['penerima_kip']),
                'nomor_kip' => $profilInput['nomor_kip'] ?? null,
                'nama_di_kip' => $profilInput['nama_di_kip'] ?? null,
                'nomor_kks' => $profilInput['nomor_kks'] ?? null,
                'layak_pip' => !empty($profilInput['layak_pip']),
                'alasan_layak_pip' => $profilInput['alasan_layak_pip'] ?? null,
                'bank' => $profilInput['bank'] ?? null,
                'nomor_rekening_bank' => $profilInput['nomor_rekening_bank'] ?? null,
                'rekening_atas_nama' => $profilInput['rekening_atas_nama'] ?? null,
            ];

            SiswaProfil::updateOrCreate(['siswa_id' => $siswa->id], $profilData);
        }

        $ortuInput = $request->input('ortu', []);
        foreach (['ayah', 'ibu', 'wali'] as $hub) {
            $data = $ortuInput[$hub] ?? [];
            $hasAny = collect($data)->except('status_hidup')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
            if ($hasAny) {
                SiswaOrangTua::updateOrCreate(
                    ['siswa_id' => $siswa->id, 'hubungan' => $hub],
                    [
                        'nama' => $data['nama'] ?? null,
                        'nik' => $data['nik'] ?? null,
                        'tahun_lahir' => isset($data['tahun_lahir']) && $data['tahun_lahir'] !== '' ? (int) $data['tahun_lahir'] : null,
                        'jenjang_pendidikan' => $data['jenjang_pendidikan'] ?? null,
                        'pekerjaan' => $data['pekerjaan'] ?? null,
                        'penghasilan' => $data['penghasilan'] ?? null,
                        'no_hp' => $data['no_hp'] ?? null,
                        'status_hidup' => $data['status_hidup'] ?? 'hidup',
                    ]
                );
            }
        }

        $periodikInput = $request->input('periodik', []);
        $hasPeriodik = collect($periodikInput)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        if ($hasPeriodik) {
            SiswaPeriodik::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => (int) $siswa->tahun_ajaran_id,
                    'semester' => $rombel?->semester ?? 'Ganjil',
                ],
                [
                    'berat_badan' => isset($periodikInput['berat_badan']) && $periodikInput['berat_badan'] !== '' ? (float) $periodikInput['berat_badan'] : null,
                    'tinggi_badan' => isset($periodikInput['tinggi_badan']) && $periodikInput['tinggi_badan'] !== '' ? (float) $periodikInput['tinggi_badan'] : null,
                    'lingkar_kepala' => isset($periodikInput['lingkar_kepala']) && $periodikInput['lingkar_kepala'] !== '' ? (float) $periodikInput['lingkar_kepala'] : null,
                    'jumlah_saudara_kandung' => isset($periodikInput['jumlah_saudara_kandung']) && $periodikInput['jumlah_saudara_kandung'] !== '' ? (int) $periodikInput['jumlah_saudara_kandung'] : null,
                    'jarak_rumah_km' => isset($periodikInput['jarak_rumah_km']) && $periodikInput['jarak_rumah_km'] !== '' ? (float) $periodikInput['jarak_rumah_km'] : null,
                ]
            );
        }

        if (!empty($siswa->kelas)) {
            RiwayatKelasSiswa::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => (int) $siswa->tahun_ajaran_id,
                    'semester' => $rombel?->semester ?? 'Ganjil',
                ],
                [
                    'rombel_id' => $rombel?->id,
                    'kelas' => $siswa->kelas,
                    'status' => ($siswa->status_siswa ?? 'aktif') === 'lulus' ? 'lulus' : (($siswa->status_siswa ?? 'aktif') === 'aktif' ? 'aktif' : 'mutasi'),
                ]
            );
        }
    }

    protected function formOptions(): array
    {
        return [
            'tahunAjarans' => TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get(),
            'rombels' => Rombel::with('tahunAjaran')->where('is_aktif', true)->orderBy('tingkat')->orderBy('nama')->get(),
            'agamaOptions' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Khonghucu', 'Kepercayaan Terhadap Tuhan YME'],
            'statusOptions' => [
                'aktif' => 'Aktif',
                'lulus' => 'Lulus / Alumni',
                'mutasi_keluar' => 'Mutasi Keluar',
                'drop_out' => 'Drop Out',
                'mengundurkan_diri' => 'Mengundurkan Diri',
                'wafat' => 'Wafat',
            ],
            'pendidikanOptions' => ['Tidak sekolah', 'PAUD', 'TK / sederajat', 'Putus SD', 'SD / sederajat', 'SMP / sederajat', 'SMA / sederajat', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'],
            'pekerjaanOptions' => ['Tidak bekerja', 'Tidak dapat diterapkan', 'Sudah Meninggal', 'Buruh', 'Karyawan Swasta', 'Wiraswasta', 'Wirausaha', 'Pedagang Kecil', 'Perdagangan', 'Petani', 'Nelayan', 'PNS/TNI/Polri', 'Aparatur Sipil Negara', 'Pensiunan', 'Lainnya'],
            'penghasilanOptions' => ['Tidak Berpenghasilan', '< Rp 1.000.000', 'Rp 1.000.000 - Rp 3.000.000', 'Rp 2.000.000 - Rp 5.000.000', 'Rp 5.000.000 - Rp 20.000.000', '> Rp 20.000.000'],
            'jenisTinggalOptions' => ['Bersama orang tua', 'Wali', 'Kos', 'Asrama', 'Panti asuhan', 'Pesantren', 'Lainnya'],
            'transportasiOptions' => ['Jalan kaki', 'Sepeda', 'Sepeda motor', 'Angkutan umum/bus/pete-pete', 'Mobil/bus antar jemput', 'Ojek', 'Kereta api', 'Lainnya'],
        ];
    }
}
