<?php

namespace Database\Seeders;

use App\Http\Controllers\Erapor\CetakRaporController;
use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\ClassHomeroom;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\RaporSetting;
use App\Models\ReportFinalization;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaOrangTua;
use App\Models\SiswaProfil;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\GradingCalculationService;
use App\Services\RaporArchiveService;
use Illuminate\Database\Seeder;

class RaporBukuIndukDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AssessmentComponentSeeder::class,
            RaporMataPelajaranSeeder::class,
        ]);

        $gradingService = app(GradingCalculationService::class);
        $archiveService = app(RaporArchiveService::class);

        $components = AssessmentComponent::whereIn('code', ['tugas', 'sh', 'asat'])->get()->keyBy('code');
        $adminUser = User::whereIn('role', ['super_admin', 'admin_sekolah'])->first();
        $kepsekUser = User::where('role', 'kepala_sekolah')->first();
        $kepsekGuru = $kepsekUser?->guru ?? Guru::where('nama_lengkap', 'like', '%Encum%')->first();

        // 1. Ensure 5 academic years (2022/2023 .. 2026/2027) exist
        $years = [];
        $yearSpecs = [
            '2022/2023' => ['2022-07-01', '2023-06-30', false],
            '2023/2024' => ['2023-07-01', '2024-06-30', false],
            '2024/2025' => ['2024-07-01', '2025-06-30', false],
            '2025/2026' => ['2025-07-01', '2026-06-30', false],
            '2026/2027' => ['2026-07-01', '2027-06-30', true],
        ];
        foreach ($yearSpecs as $nama => [$start, $end, $aktif]) {
            $years[$nama] = TahunAjaran::firstOrCreate(
                ['nama' => $nama],
                [
                    'tanggal_mulai' => $start,
                    'tanggal_selesai' => $end,
                    'is_aktif' => $aktif,
                ]
            );
        }
        $activeYear = $years['2026/2027'];

        // 2. Ensure RaporSetting & PengaturanSekolah use SMK PGRI 5 SERPONG metadata if still default
        $raporSetting = RaporSetting::instance();
        $settingUpdates = [];
        if (empty($raporSetting->header_sekolah) || str_contains(strtoupper($raporSetting->header_sekolah), 'MADINAH') || str_contains(strtoupper($raporSetting->header_yayasan ?? ''), 'PGRI) PGRI')) {
            $settingUpdates['header_yayasan'] = 'YAYASAN PEMBINA LEMBAGA PENDIDIKAN (YPLP)';
            $settingUpdates['header_sekolah'] = 'SMK PGRI 5 SERPONG';
        }
        if (empty($raporSetting->nama_kepala_sekolah)) {
            $settingUpdates['nama_kepala_sekolah'] = $kepsekGuru?->nama_lengkap ?? 'Encum Sumiati, S.Pd';
            $settingUpdates['nip_kepala_sekolah'] = $kepsekGuru?->nip ?? '202607010001';
        }
        if (empty($raporSetting->kota_pembagian) || $raporSetting->kota_pembagian === 'Bogor') {
            $settingUpdates['kota_pembagian'] = 'Kota Tangerang Selatan';
        }
        if (!empty($settingUpdates)) {
            $raporSetting->update($settingUpdates);
        }

        $schoolSetting = \App\Models\PengaturanSekolah::first();
        if ($schoolSetting && (empty($schoolSetting->alamat_sekolah) || str_contains($schoolSetting->alamat_sekolah, 'Serpong, Kota Tangerang Selatan, Banten'))) {
            $schoolSetting->update([
                'alamat_sekolah' => 'Jl. Raya Puspiptek (Depan Masjid Agung) Serpong - Tangerang Selatan',
            ]);
        }

        // 3. Assign Wali Kelas (ClassHomeroom) across all classes & academic years
        $gurus = Guru::orderBy('id')->get();
        if ($gurus->isEmpty()) {
            $demoGuruUser = User::firstOrCreate(
                ['email' => 'walikelas.demo@smkpgri5.sch.id'],
                [
                    'name' => 'Wali Kelas Demo, S.Pd',
                    'password' => bcrypt('password'),
                    'role' => 'guru',
                    'is_active' => true,
                ]
            );
            $gurus = collect([
                Guru::create([
                    'user_id' => $demoGuruUser->id,
                    'nip' => '202607010099',
                    'nama_lengkap' => 'Wali Kelas Demo, S.Pd',
                    'jenis_kelamin' => 'L',
                ]),
            ]);
        }

        $allClasses = [
            'X AK', 'X BD', 'X MP 1', 'X MP 2',
            'XI AK 1', 'XI AK 2', 'XI BD', 'XI MP 1', 'XI MP 2',
            'XII AK', 'XII BD', 'XII MP 1', 'XII MP 2',
        ];

        foreach ($years as $yearModel) {
            foreach ($allClasses as $idx => $cls) {
                $guru = $gurus[$idx % $gurus->count()];
                ClassHomeroom::firstOrCreate(
                    [
                        'kelas' => $cls,
                        'tahun_ajaran_id' => $yearModel->id,
                    ],
                    [
                        'guru_id' => $guru->id,
                        'nama_wali_kelas' => $guru->nama_lengkap,
                        'nip_wali_kelas' => $guru->nip,
                        'kkm_default' => 75.00,
                    ]
                );
            }
        }

        // 4. Seed Active Students (Kelas X, XI, XII across AKL, MPLB, BD)
        $activeSpecs = [
            'X AK' => ['jurusan' => 'AK', 'past' => []],
            'X MP 1' => ['jurusan' => 'MP', 'past' => []],
            'X BD' => ['jurusan' => 'BD', 'past' => []],
            'XI AK 1' => ['jurusan' => 'AK', 'past' => [
                ['2025/2026', 'Ganjil', 'X AK', 'naik_kelas'],
                ['2025/2026', 'Genap', 'X AK', 'naik_kelas'],
            ]],
            'XI MP 1' => ['jurusan' => 'MP', 'past' => [
                ['2025/2026', 'Ganjil', 'X MP 1', 'naik_kelas'],
                ['2025/2026', 'Genap', 'X MP 1', 'naik_kelas'],
            ]],
            'XI BD' => ['jurusan' => 'BD', 'past' => [
                ['2025/2026', 'Ganjil', 'X BD', 'naik_kelas'],
                ['2025/2026', 'Genap', 'X BD', 'naik_kelas'],
            ]],
            'XII AK' => ['jurusan' => 'AK', 'past' => [
                ['2024/2025', 'Ganjil', 'X AK', 'naik_kelas'],
                ['2024/2025', 'Genap', 'X AK', 'naik_kelas'],
                ['2025/2026', 'Ganjil', 'XI AK 1', 'naik_kelas'],
                ['2025/2026', 'Genap', 'XI AK 1', 'naik_kelas'],
            ]],
            'XII MP 1' => ['jurusan' => 'MP', 'past' => [
                ['2024/2025', 'Ganjil', 'X MP 1', 'naik_kelas'],
                ['2024/2025', 'Genap', 'X MP 1', 'naik_kelas'],
                ['2025/2026', 'Ganjil', 'XI MP 1', 'naik_kelas'],
                ['2025/2026', 'Genap', 'XI MP 1', 'naik_kelas'],
            ]],
            'XII BD' => ['jurusan' => 'BD', 'past' => [
                ['2024/2025', 'Ganjil', 'X BD', 'naik_kelas'],
                ['2024/2025', 'Genap', 'X BD', 'naik_kelas'],
                ['2025/2026', 'Ganjil', 'XI BD', 'naik_kelas'],
                ['2025/2026', 'Genap', 'XI BD', 'naik_kelas'],
            ]],
        ];

        $demoActiveStudents = [];

        foreach ($activeSpecs as $kelas => $spec) {
            $students = Siswa::where('kelas', $kelas)
                ->where(function ($q) {
                    $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                })
                ->orderBy('nama_lengkap')
                ->take(5)
                ->get();

            if ($students->isEmpty()) {
                continue;
            }

            $subjectsCurrent = MataPelajaran::forClass($kelas, $activeYear->id, 'Ganjil');
            $rombelCurrent = Rombel::where('nama', $kelas)->where('tahun_ajaran_id', $activeYear->id)->first();

            // Provision NH 1 + ASAT items for current semester (and NH 2 sample for XI MP 1)
            $itemsBySubject = [];
            foreach ($subjectsCurrent as $subject) {
                $blocks = [1];
                if ($kelas === 'XI MP 1' && in_array($subject->kode, ['B.01', 'MP-B2'], true)) {
                    $blocks[] = 2;
                }

                $subItems = [];
                foreach ($blocks as $blockNum) {
                    $subItems[] = AssessmentItem::firstOrCreate(
                        [
                            'assessment_component_id' => $components['tugas']->id,
                            'mata_pelajaran_id' => $subject->id,
                            'tahun_ajaran_id' => $activeYear->id,
                            'semester' => 'Ganjil',
                            'daily_block' => $blockNum,
                            'weight_group' => 'tugas',
                            'sequence' => 1,
                        ],
                        [
                            'title' => 'Tugas 1',
                            'max_score' => 100.0,
                        ]
                    );

                    $subItems[] = AssessmentItem::firstOrCreate(
                        [
                            'assessment_component_id' => $components['sh']->id,
                            'mata_pelajaran_id' => $subject->id,
                            'tahun_ajaran_id' => $activeYear->id,
                            'semester' => 'Ganjil',
                            'daily_block' => $blockNum,
                            'weight_group' => 'sumatif',
                            'sequence' => 1,
                        ],
                        [
                            'title' => 'Sumatif Harian 1',
                            'max_score' => 100.0,
                        ]
                    );
                }

                $subItems[] = AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $components['asat']->id,
                        'mata_pelajaran_id' => $subject->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => 'Ganjil',
                        'daily_block' => null,
                        'weight_group' => 'asat',
                        'sequence' => 1,
                    ],
                    [
                        'title' => 'Nilai Murni ASAT',
                        'max_score' => 100.0,
                    ]
                );

                $itemsBySubject[$subject->id] = $subItems;
            }

            foreach ($students as $stIdx => $siswa) {
                $this->ensureBiodata($siswa, $stIdx);

                RiwayatKelasSiswa::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => 'Ganjil',
                    ],
                    [
                        'rombel_id' => $rombelCurrent?->id,
                        'kelas' => $kelas,
                        'status' => 'aktif',
                    ]
                );

                // Current semester item scores -> recalculateSubjectGrade()
                foreach ($subjectsCurrent as $subject) {
                    $baseAbility = 76 + (($siswa->id * 7 + $subject->id * 3) % 19); // 76..94
                    foreach ($itemsBySubject[$subject->id] as $item) {
                        $delta = (($item->id * 5 + $siswa->id * 3) % 7) - 3; // -3..+3
                        $score = min(98.0, max(70.0, round($baseAbility + $delta, 1)));

                        StudentAssessmentScore::updateOrCreate(
                            [
                                'siswa_id' => $siswa->id,
                                'assessment_item_id' => $item->id,
                            ],
                            [
                                'score' => $score,
                                'source_type' => $item->weight_group === 'asat' ? 'cbt_api' : 'manual',
                                'notes' => 'Data demo Buku Nilai E-Rapor',
                                'updated_by' => $adminUser?->id,
                            ]
                        );
                    }

                    $summary = $gradingService->recalculateSubjectGrade(
                        $siswa->id,
                        $subject->id,
                        $activeYear->id,
                        'Ganjil'
                    );
                    $pred = CetakRaporController::calculatePredikat($summary->final_score);
                    $summary->update([
                        'capaian_kompetensi' => CetakRaporController::generateDeskripsi($subject->nama, $summary->final_score, $pred),
                    ]);
                }

                // Historical past semesters for Kelas XI & XII active students
                foreach ($spec['past'] as $pIdx => [$yrName, $sem, $pastKelas, $histStatus]) {
                    $pastYear = $years[$yrName];
                    $this->seedSummaryPeriod(
                        siswa: $siswa,
                        tahunAjaran: $pastYear,
                        semester: $sem,
                        kelas: $pastKelas,
                        historyStatus: $histStatus,
                        rank: ($stIdx % 5) + 1,
                        totalSiswaKelas: $students->count(),
                        progressionBias: $pIdx + 1,
                        archiveService: $archiveService,
                        adminId: $adminUser?->id
                    );
                }

                $demoActiveStudents[] = [$siswa, $kelas];
            }

            // Recalculate current class STS/ASAT rank and freeze archive for current active cohort
            $gradingService->recalculateClassSTS($kelas, $activeYear->id, 'Ganjil');
            foreach ($students as $siswa) {
                $stsSum = StudentStsSummary::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $activeYear->id)
                    ->where('semester', 'Ganjil')
                    ->first();
                if ($stsSum) {
                    $stsSum->update([
                        'catatan_wali_kelas' => $this->buildCatatanWali($siswa->nama_lengkap, $stsSum->average_score),
                        'ekstrakurikuler' => $this->buildDemoEkskul($siswa->id),
                    ]);
                }
                $archiveService->archiveStudent($siswa, $activeYear->id, 'Ganjil', 'uas', $adminUser?->id, recalculate: false);
            }
        }

        // 5. Seed Multi-Cohort Alumni (Angkatan 2026 & Angkatan 2025, full 6 semesters each)
        $alumniSpecs = [
            // Alumni 2026 (2023/2024 X -> 2024/2025 XI -> 2025/2026 XII, Lulus 2026)
            [
                'nisn' => '0071002026',
                'nipd' => '2324.10.001',
                'nama_lengkap' => 'Aulia Putri Ramadhani',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Tangerang Selatan',
                'tanggal_lahir' => '2008-04-12',
                'agama' => 'Islam',
                'kelas_x' => 'X AK',
                'kelas_xi' => 'XI AK 1',
                'kelas_xii' => 'XII AK',
                'tahun_lulus' => 2026,
                'tahun_ajaran_lulus' => '2025/2026',
                'tanggal_masuk' => '2023-07-15',
                'tanggal_keluar' => '2026-05-05',
                'ijazah' => 'DN-02/M-SMK/26/0012041',
                'sekolah_asal' => 'SMP Negeri 8 Tangerang Selatan',
                'ayah' => 'Hendra Wijaya',
                'ibu' => 'Ratna Sari Dewi',
                'alamat' => 'Jl. Raya Serpong KM 8 No. 14, Pondok Jagung, Serpong Utara',
                'timeline' => ['2023/2024', '2024/2025', '2025/2026'],
                'base' => 88,
            ],
            [
                'nisn' => '0072002026',
                'nipd' => '2324.10.002',
                'nama_lengkap' => 'Rizky Pratama Nugroho',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Tangerang',
                'tanggal_lahir' => '2008-08-21',
                'agama' => 'Islam',
                'kelas_x' => 'X MP 1',
                'kelas_xi' => 'XI MP 1',
                'kelas_xii' => 'XII MP 1',
                'tahun_lulus' => 2026,
                'tahun_ajaran_lulus' => '2025/2026',
                'tanggal_masuk' => '2023-07-15',
                'tanggal_keluar' => '2026-05-05',
                'ijazah' => 'DN-02/M-SMK/26/0012042',
                'sekolah_asal' => 'SMP Islam Al-Azhar BSD',
                'ayah' => 'Bambang Nugroho',
                'ibu' => 'Sri Wahyuni',
                'alamat' => 'Perumahan Griya Loka Sektor 1.4 Blok B3 No. 9, Serpong',
                'timeline' => ['2023/2024', '2024/2025', '2025/2026'],
                'base' => 84,
            ],
            [
                'nisn' => '0073002026',
                'nipd' => '2324.10.003',
                'nama_lengkap' => 'Nadia Salsabila Azzahra',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2008-11-03',
                'agama' => 'Islam',
                'kelas_x' => 'X BD',
                'kelas_xi' => 'XI BD',
                'kelas_xii' => 'XII BD',
                'tahun_lulus' => 2026,
                'tahun_ajaran_lulus' => '2025/2026',
                'tanggal_masuk' => '2023-07-15',
                'tanggal_keluar' => '2026-05-05',
                'ijazah' => 'DN-02/M-SMK/26/0012043',
                'sekolah_asal' => 'SMP Negeri 11 Tangerang Selatan',
                'ayah' => 'Fajar Kurniawan',
                'ibu' => 'Lestari Handayani',
                'alamat' => 'Jl. Ciater Raya No. 27, Rawa Mekar Jaya, Serpong',
                'timeline' => ['2023/2024', '2024/2025', '2025/2026'],
                'base' => 86,
            ],
            // Alumni 2025 (2022/2023 X -> 2023/2024 XI -> 2024/2025 XII, Lulus 2025)
            [
                'nisn' => '0061002025',
                'nipd' => '2223.10.001',
                'nama_lengkap' => 'Dimas Aditya Mahendra',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bogor',
                'tanggal_lahir' => '2007-02-19',
                'agama' => 'Islam',
                'kelas_x' => 'X AK',
                'kelas_xi' => 'XI AK 1',
                'kelas_xii' => 'XII AK',
                'tahun_lulus' => 2025,
                'tahun_ajaran_lulus' => '2024/2025',
                'tanggal_masuk' => '2022-07-18',
                'tanggal_keluar' => '2025-05-05',
                'ijazah' => 'DN-02/M-SMK/25/0009811',
                'sekolah_asal' => 'SMP Negeri 4 Tangerang Selatan',
                'ayah' => 'Agung Mahendra',
                'ibu' => 'Endang Sulistiyowati',
                'alamat' => 'Jl. Bukit Indah Blok C2 No. 11, Serua, Ciputat',
                'timeline' => ['2022/2023', '2023/2024', '2024/2025'],
                'base' => 87,
            ],
            [
                'nisn' => '0062002025',
                'nipd' => '2223.10.002',
                'nama_lengkap' => 'Siti Nurhaliza Fitriani',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Tangerang Selatan',
                'tanggal_lahir' => '2007-06-14',
                'agama' => 'Islam',
                'kelas_x' => 'X MP 1',
                'kelas_xi' => 'XI MP 1',
                'kelas_xii' => 'XII MP 1',
                'tahun_lulus' => 2025,
                'tahun_ajaran_lulus' => '2024/2025',
                'tanggal_masuk' => '2022-07-18',
                'tanggal_keluar' => '2025-05-05',
                'ijazah' => 'DN-02/M-SMK/25/0009812',
                'sekolah_asal' => 'MTs Negeri 1 Tangerang Selatan',
                'ayah' => 'Mulyadi Hasan',
                'ibu' => 'Aminah Rahmawati',
                'alamat' => 'Jl. Bhayangkara No. 42, Pakujaya, Serpong Utara',
                'timeline' => ['2022/2023', '2023/2024', '2024/2025'],
                'base' => 90,
            ],
            [
                'nisn' => '0063002025',
                'nipd' => '2223.10.003',
                'nama_lengkap' => 'Bagas Kurniawan Saputra',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Depok',
                'tanggal_lahir' => '2007-09-28',
                'agama' => 'Islam',
                'kelas_x' => 'X BD',
                'kelas_xi' => 'XI BD',
                'kelas_xii' => 'XII BD',
                'tahun_lulus' => 2025,
                'tahun_ajaran_lulus' => '2024/2025',
                'tanggal_masuk' => '2022-07-18',
                'tanggal_keluar' => '2025-05-05',
                'ijazah' => 'DN-02/M-SMK/25/0009813',
                'sekolah_asal' => 'SMP Negeri 19 Tangerang Selatan',
                'ayah' => 'Dedi Saputra',
                'ibu' => 'Yanti Kusuma',
                'alamat' => 'Jl. Villa Melati Mas Blok G7 No. 5, Jelupang, Serpong Utara',
                'timeline' => ['2022/2023', '2023/2024', '2024/2025'],
                'base' => 83,
            ],
        ];

        foreach ($alumniSpecs as $alIdx => $al) {
            $taLulus = $years[$al['tahun_ajaran_lulus']];
            $lastRombel = Rombel::where('nama', $al['kelas_xii'])->where('tahun_ajaran_id', $activeYear->id)->first();

            $siswa = Siswa::updateOrCreate(
                ['nisn' => $al['nisn']],
                [
                    'nipd' => $al['nipd'],
                    'nik' => '367401' . str_pad((string) (1000000000 + $alIdx * 137), 10, '0', STR_PAD_LEFT),
                    'nama_lengkap' => $al['nama_lengkap'],
                    'jenis_kelamin' => $al['jenis_kelamin'],
                    'tempat_lahir' => $al['tempat_lahir'],
                    'tanggal_lahir' => $al['tanggal_lahir'],
                    'agama' => $al['agama'],
                    'kelas' => $al['kelas_xii'],
                    'rombel_id' => $lastRombel?->id,
                    'alamat' => $al['alamat'],
                    'tahun_ajaran_id' => $taLulus->id,
                    'status_siswa' => 'lulus',
                    'tahun_lulus' => $al['tahun_lulus'],
                    'tahun_ajaran_lulus_id' => $taLulus->id,
                    'tanggal_masuk' => $al['tanggal_masuk'],
                    'tanggal_keluar' => $al['tanggal_keluar'],
                    'alasan_keluar' => 'Lulus Satuan Pendidikan',
                    'sync_source' => 'demo_seeder',
                ]
            );

            SiswaProfil::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'alamat_jalan' => $al['alamat'],
                    'kelurahan' => 'Pondok Jagung',
                    'kecamatan' => 'Serpong Utara',
                    'kode_pos' => '15326',
                    'sekolah_asal' => $al['sekolah_asal'],
                    'no_seri_ijazah' => $al['ijazah'],
                    'skhun' => 'SKHUN-' . $al['tahun_lulus'] . '-' . ($alIdx + 1),
                    'anak_keberapa' => ($alIdx % 3) + 1,
                ]
            );

            SiswaOrangTua::updateOrCreate(
                ['siswa_id' => $siswa->id, 'hubungan' => 'ayah'],
                [
                    'nama' => $al['ayah'],
                    'pekerjaan' => 'Karyawan Swasta',
                    'jenjang_pendidikan' => 'S1',
                    'no_hp' => '0812' . str_pad((string) (80001000 + $alIdx), 8, '0', STR_PAD_LEFT),
                    'status_hidup' => 'hidup',
                ]
            );

            SiswaOrangTua::updateOrCreate(
                ['siswa_id' => $siswa->id, 'hubungan' => 'ibu'],
                [
                    'nama' => $al['ibu'],
                    'pekerjaan' => 'Ibu Rumah Tangga',
                    'jenjang_pendidikan' => 'SMA',
                    'no_hp' => '0813' . str_pad((string) (90001000 + $alIdx), 8, '0', STR_PAD_LEFT),
                    'status_hidup' => 'hidup',
                ]
            );

            [$y1, $y2, $y3] = $al['timeline'];
            $periods = [
                [$years[$y1], 'Ganjil', $al['kelas_x'], 'naik_kelas', 0],
                [$years[$y1], 'Genap', $al['kelas_x'], 'naik_kelas', 1],
                [$years[$y2], 'Ganjil', $al['kelas_xi'], 'naik_kelas', 2],
                [$years[$y2], 'Genap', $al['kelas_xi'], 'naik_kelas', 3],
                [$years[$y3], 'Ganjil', $al['kelas_xii'], 'lulus', 4],
                [$years[$y3], 'Genap', $al['kelas_xii'], 'lulus', 5],
            ];

            foreach ($periods as [$taModel, $sem, $kelasSem, $histStatus, $stepBias]) {
                $this->seedSummaryPeriod(
                    siswa: $siswa,
                    tahunAjaran: $taModel,
                    semester: $sem,
                    kelas: $kelasSem,
                    historyStatus: $histStatus,
                    rank: ($alIdx % 3) + 1,
                    totalSiswaKelas: 36,
                    progressionBias: $stepBias,
                    archiveService: $archiveService,
                    adminId: $adminUser?->id,
                    baseAbility: $al['base']
                );
            }
        }

        // 6. Mark finalization status for seeded active classes in 2026/2027 Ganjil
        foreach (array_keys($activeSpecs) as $cls) {
            ReportFinalization::updateOrCreate(
                [
                    'kelas' => $cls,
                    'tahun_ajaran_id' => $activeYear->id,
                    'semester' => 'Ganjil',
                ],
                [
                    'status' => 'final',
                    'reviewed_by' => $adminUser?->id,
                    'approved_by' => $kepsekUser?->id ?? $adminUser?->id,
                    'finalized_at' => now(),
                    'notes' => 'Difinalisasi otomatis melalui RaporBukuIndukDemoSeeder.',
                ]
            );
        }
    }

    protected function ensureBiodata(Siswa $siswa, int $idx): void
    {
        $updates = [];
        if (empty($siswa->tempat_lahir)) {
            $updates['tempat_lahir'] = 'Tangerang Selatan';
        }
        if (empty($siswa->tanggal_lahir)) {
            $updates['tanggal_lahir'] = '2009-05-' . str_pad((string) (10 + ($idx % 18)), 2, '0', STR_PAD_LEFT);
        }
        if (empty($siswa->agama)) {
            $updates['agama'] = 'Islam';
        }
        if (empty($siswa->alamat)) {
            $updates['alamat'] = 'Jl. Raya Serpong KM 7, Tangerang Selatan';
        }
        if (!empty($updates)) {
            $siswa->update($updates);
        }

        SiswaProfil::firstOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'alamat_jalan' => $siswa->alamat ?? 'Jl. Raya Serpong KM 7',
                'kelurahan' => 'Lengkong Karya',
                'kecamatan' => 'Serpong Utara',
                'kode_pos' => '15310',
                'sekolah_asal' => 'SMP Negeri Tangerang Selatan',
            ]
        );

        SiswaOrangTua::firstOrCreate(
            ['siswa_id' => $siswa->id, 'hubungan' => 'ayah'],
            ['nama' => 'Bapak ' . explode(' ', $siswa->nama_lengkap)[0], 'pekerjaan' => 'Wiraswasta', 'status_hidup' => 'hidup']
        );
        SiswaOrangTua::firstOrCreate(
            ['siswa_id' => $siswa->id, 'hubungan' => 'ibu'],
            ['nama' => 'Ibu ' . explode(' ', $siswa->nama_lengkap)[0], 'pekerjaan' => 'Ibu Rumah Tangga', 'status_hidup' => 'hidup']
        );
    }

    protected function seedSummaryPeriod(
        Siswa $siswa,
        TahunAjaran $tahunAjaran,
        string $semester,
        string $kelas,
        string $historyStatus,
        int $rank,
        int $totalSiswaKelas,
        int $progressionBias,
        RaporArchiveService $archiveService,
        ?int $adminId = null,
        int $baseAbility = 82
    ): void {
        $rombel = Rombel::where('nama', $kelas)->orderByDesc('tahun_ajaran_id')->first();

        RiwayatKelasSiswa::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'semester' => $semester,
            ],
            [
                'rombel_id' => $rombel?->id,
                'kelas' => $kelas,
                'status' => $historyStatus,
            ]
        );

        $subjects = MataPelajaran::forClass($kelas, $tahunAjaran->id, $semester, $siswa->id);
        $totalScore = 0.0;
        $count = 0;

        foreach ($subjects as $subject) {
            $seed = ($siswa->id * 3 + $subject->id * 5 + $progressionBias * 2) % 9;
            $tugasAvg = min(98.0, max(72.0, round($baseAbility + $seed - 2, 1)));
            $shAvg = min(98.0, max(72.0, round($baseAbility + $seed - 1, 1)));
            $nh1Final = round((0.40 * $tugasAvg) + (0.60 * $shAvg), 1);
            $asatScore = min(99.0, max(70.0, round($baseAbility + $seed, 1)));
            $finalScore = round(($nh1Final + $asatScore) / 2, 1);

            $pred = CetakRaporController::calculatePredikat($finalScore);
            $desc = CetakRaporController::generateDeskripsi($subject->nama, $finalScore, $pred);

            SubjectGradeSummary::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'mata_pelajaran_id' => $subject->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'semester' => $semester,
                ],
                [
                    'kelas' => $kelas,
                    'tugas_avg' => $tugasAvg,
                    'uh_avg' => $shAvg,
                    'projek_avg' => $shAvg,
                    'daily_scores' => [
                        '1' => [
                            'tugas_avg' => $tugasAvg,
                            'sumatif_avg' => $shAvg,
                            'final' => $nh1Final,
                        ],
                    ],
                    'sh_avg' => $shAvg,
                    'asat_score' => $asatScore,
                    'sts_score' => $asatScore,
                    'final_score' => $finalScore,
                    'status' => 'final',
                    'capaian_kompetensi' => $desc,
                ]
            );

            $totalScore += $finalScore;
            $count++;
        }

        $avgScore = $count > 0 ? round($totalScore / $count, 1) : null;

        StudentStsSummary::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'semester' => $semester,
            ],
            [
                'kelas' => $kelas,
                'total_score' => round($totalScore, 1),
                'average_score' => $avgScore,
                'rank' => $rank,
                'completed_subjects_count' => $count,
                'total_subjects_count' => $count,
                'status' => 'complete',
                'catatan_wali_kelas' => $this->buildCatatanWali($siswa->nama_lengkap, $avgScore),
                'ekstrakurikuler' => $this->buildDemoEkskul($siswa->id),
            ]
        );

        $archiveService->archiveStudent($siswa, $tahunAjaran->id, $semester, 'uas', $adminId, recalculate: false);
    }

    protected function buildDemoEkskul(int $seed): array
    {
        $pool = [
            ['nama' => 'Pramuka', 'keterangan' => 'A'],
            ['nama' => 'Paskibra', 'keterangan' => 'A'],
            ['nama' => 'Tari', 'keterangan' => 'A'],
            ['nama' => 'Futsal', 'keterangan' => 'B'],
            ['nama' => 'Rohis', 'keterangan' => 'A'],
            ['nama' => 'English Club', 'keterangan' => 'A'],
        ];

        return [
            $pool[$seed % count($pool)],
            $pool[($seed + 2) % count($pool)],
        ];
    }

    protected function buildCatatanWali(string $nama, ?float $avg): string
    {
        if ($avg !== null && $avg >= 88) {
            return "Ananda {$nama} menunjukkan capaian akademik dan sikap kedisiplinan yang sangat baik. Pertahankan prestasi gemilang ini.";
        }
        if ($avg !== null && $avg >= 80) {
            return "Ananda {$nama} mengikuti pembelajaran dengan tekun dan bertanggung jawab. Terus tingkatkan konsistensi belajar.";
        }

        return "Ananda {$nama} menunjukkan perkembangan belajar yang baik. Disarankan terus aktif berlatih dan berdiskusi di kelas.";
    }
}
