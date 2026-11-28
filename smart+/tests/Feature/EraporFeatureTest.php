<?php

namespace Tests\Feature;

use App\Models\AssessmentComponent;
use App\Models\ClassHomeroom;
use App\Models\MataPelajaran;
use App\Models\RaporSetting;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AssessmentComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EraporFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private User $adminUser;
    private \App\Models\Guru $guru;
    private Siswa $siswa;
    private MataPelajaran $mapel;
    private TahunAjaran $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AssessmentComponentSeeder::class);

        $this->teacherUser = User::factory()->create([
            'role' => 'guru',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin_sekolah',
        ]);

        $this->guru = \App\Models\Guru::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010011001',
            'nama_lengkap' => 'Guru Pengampu',
            'jenis_kelamin' => 'L',
        ]);

        $studentUser = User::factory()->create([
            'role' => 'siswa',
        ]);

        $this->tahun = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => now()->startOfYear(),
            'tanggal_selesai' => now()->endOfYear(),
            'is_aktif' => true,
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Matematika',
            'kode' => 'MTK',
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $studentUser->id,
            'nisn' => '0098765432',
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Assign $this->guru as Wali Kelas of VII - Ibnu Taimiyah
        ClassHomeroom::create([
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'guru_id' => $this->guru->id,
            'nama_wali_kelas' => $this->guru->nama_lengkap,
            'nip_wali_kelas' => $this->guru->nip,
        ]);

        // Assign default teaching schedule for $this->guru
        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Kampus Utama',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);
    }

    public function test_guest_cannot_access_erapor(): void
    {
        $response = $this->get('/erapor');
        $response->assertRedirect('/login');
    }

    public function test_teacher_can_access_erapor_dashboard(): void
    {
        // Admin sekolah, kepala sekolah, super admin can access E-Rapor dashboard
        $response = $this->actingAs($this->adminUser)->get('/erapor');
        $response->assertStatus(200)
            ->assertSee('Platform E-Rapor Terintegrasi');

        // Guru cannot access restricted E-Rapor management pages
        $this->actingAs($this->teacherUser)->get('/erapor')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cbt')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/buku-induk')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(403);
        $this->actingAs($this->teacherUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah')->assertStatus(403);
    }

    public function test_teacher_can_access_buku_nilai(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $response->assertStatus(200)
            ->assertSee('Buku Nilai Mata Pelajaran')
            ->assertSee('Budi Santoso');
    }

    public function test_update_score_recalculates_subject_summary(): void
    {
        // First get the buku nilai to ensure items are created
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $item = \App\Models\AssessmentItem::first();

        $response = $this->actingAs($this->teacherUser)
            ->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $item->id,
                'score' => 88.5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $item->id,
            'score' => 88.5,
        ]);
    }

    public function test_super_admin_and_admin_can_also_input_daily_scores(): void
    {
        $adminUser = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $item = \App\Models\AssessmentItem::first();

        $response = $this->actingAs($adminUser)
            ->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $item->id,
                'score' => 96.0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $item->id,
            'score' => 96.0,
            'updated_by' => $adminUser->id,
        ]);
    }

    public function test_rekap_sts_view_and_calculation(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/recap?kelas=VII - Ibnu Taimiyah');

        $response->assertStatus(200)
            ->assertSee('Leger Raport')
            ->assertSee('Cetak PDF')
            ->assertSee('Budi Santoso');

        // Verify landscape PDF print view renders Leger Raport cleanly
        $printPdfRes = $this->actingAs($this->teacherUser)
            ->get('/erapor/recap/print/VII - Ibnu Taimiyah');
        $printPdfRes->assertStatus(200)
            ->assertSee('LEGER RAPORT')
            ->assertSee('Budi Santoso')
            ->assertSee('Matematika')
            ->assertSee('Rata-rata Kelas')
            ->assertSee('data:image/png;base64,')
            ->assertSee('Wali Kelas');
    }

    public function test_export_class_sts_returns_excel_stream(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/recap/export/VII - Ibnu Taimiyah');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('content-type'));
    }

    public function test_sync_cbt_via_api_successfully_creates_scores_and_audit(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/exam-results*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 101,
                        'exam_external_id' => 'EXAM-CBT-001',
                        'exam_name' => 'STS 1 Matematika VII',
                        'student_nisn' => '0098765432',
                        'student_name' => 'Budi Santoso',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 95.0,
                        'status' => 'submitted',
                        'started_at' => now()->subHour()->toIso8601String(),
                        'submitted_at' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-cbt', [
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_results', [
            'student_nisn' => '0098765432',
            'score' => 95.0,
        ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'score' => 95.0,
            'source_type' => 'cbt_api',
        ]);

        $this->assertDatabaseHas('erapor_audit_logs', [
            'action' => 'sync_cbt',
        ]);
    }

    public function test_sync_lms_task_scores_maps_pengumpulan_tugas(): void
    {
        $tugas = \App\Models\Tugas::create([
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'judul' => 'Tugas Aljabar 1',
            'deskripsi' => 'Kerjakan soal 1-10',
            'tenggat_waktu' => now()->addDays(2),
        ]);

        \App\Models\PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $this->siswa->id,
            'file_path' => 'tugas/sample.pdf',
            'waktu_pengumpulan' => now(),
            'status' => 'dinilai',
            'nilai' => 92.0,
            'catatan_guru' => 'Bagus sekali',
        ]);

        // Get buku-nilai first to generate assessment items
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $tugasItem = \App\Models\AssessmentItem::whereHas('component', function ($q) {
            $q->where('code', 'tugas');
        })->first();

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-lms', [
                'tugas_id' => $tugas->id,
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'sequence' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'score' => 92.0,
            'source_type' => 'lms',
        ]);
    }

    public function test_competition_ranking_matches_excel_rank_eq_rules(): void
    {
        $gradingService = app(\App\Services\GradingCalculationService::class);

        // Create 3 additional students in class 'VII - Ibnu Taimiyah'
        $s2 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765433',
            'nama_lengkap' => 'Student 2',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);
        $s3 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765434',
            'nama_lengkap' => 'Student 3',
            'jenis_kelamin' => 'P',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);
        $s4 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765435',
            'nama_lengkap' => 'Student 4',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Manually create SubjectGradeSummary to simulate STS scores
        // Student 1: 95
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 95.0,
        ]);
        // Student 2: 90
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s2->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 90.0,
        ]);
        // Student 3: 90 (TIE)
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s3->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 90.0,
        ]);
        // Student 4: 85
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s4->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 85.0,
        ]);

        // Recalculate Class STS
        $gradingService->recalculateClassSTS('VII - Ibnu Taimiyah', $this->tahun->id, 'Ganjil');

        $sts1 = \App\Models\StudentStsSummary::where('siswa_id', $this->siswa->id)->first();
        $sts2 = \App\Models\StudentStsSummary::where('siswa_id', $s2->id)->first();
        $sts3 = \App\Models\StudentStsSummary::where('siswa_id', $s3->id)->first();
        $sts4 = \App\Models\StudentStsSummary::where('siswa_id', $s4->id)->first();

        // Check RANK.EQ: [95 -> rank 1, 90 -> rank 2, 90 -> rank 2, 85 -> rank 4]
        $this->assertEquals(1, $sts1->rank);
        $this->assertEquals(2, $sts2->rank);
        $this->assertEquals(2, $sts3->rank);
        $this->assertEquals(4, $sts4->rank);
    }

    public function test_teacher_can_create_and_delete_dynamic_assessment_item(): void
    {
        $comp = \App\Models\AssessmentComponent::where('code', 'uh')->first();

        // 1. Create dynamic item via web form
        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/item', [
                'assessment_component_id' => $comp->id,
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'title' => 'UH 3: Sistem Koordinat',
                'max_score' => 100,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('assessment_items', [
            'title' => 'UH 3: Sistem Koordinat',
            'mata_pelajaran_id' => $this->mapel->id,
        ]);

        $item = \App\Models\AssessmentItem::where('title', 'UH 3: Sistem Koordinat')->first();

        // 2. Delete item
        $delResponse = $this->actingAs($this->teacherUser)
            ->delete('/erapor/buku-nilai/item/' . $item->id);

        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('assessment_items', [
            'id' => $item->id,
        ]);
    }

    public function test_remedial_sync_mode_picks_highest_score(): void
    {
        // Get buku-nilai to ensure items exist
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $asatItem = \App\Models\AssessmentItem::whereHas('component', fn($q) => $q->where('code', 'asat'))->first();

        // Student initial low score = 65
        \App\Models\StudentAssessmentScore::create([
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $asatItem->id,
            'score' => 65.0,
            'source_type' => 'cbt_api',
        ]);

        // Mock Remedial Exam result = 85
        \Illuminate\Support\Facades\Http::fake([
            '*/exam-results*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 999,
                        'exam_external_id' => 'REM-STS-001',
                        'exam_name' => 'Remedial ASAT Matematika',
                        'student_nisn' => '0098765432',
                        'student_name' => 'Budi Santoso',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 85.0,
                        'status' => 'submitted',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-cbt', [
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
                'target_assessment_item_id' => $asatItem->id,
                'sync_mode' => 'highest',
            ]);

        $response->assertRedirect();

        // Score should now be 85.0 (the higher score)
        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $asatItem->id,
            'score' => 85.0,
        ]);
    }

    public function test_user_can_access_buku_induk_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/erapor/buku-induk');

        $response->assertStatus(200)
            ->assertSee('Buku Induk')
            ->assertSee('Budi Santoso')
            ->assertSee('0098765432');
    }

    public function test_user_can_access_buku_induk_detail(): void
    {
        // Add a grade summary to test transcript display
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'sts_score' => 88.5,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id);

        $response->assertStatus(200)
            ->assertSee('Buku Induk')
            ->assertSee('Transkrip')
            ->assertSee('Budi Santoso')
            ->assertSee('Matematika')
            ->assertSee('88.5');
    }

    public function test_user_can_access_buku_induk_print_and_export(): void
    {
        // Test Print
        $printRes = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id . '/print');
        $printRes->assertStatus(200)
            ->assertSee('BUKU INDUK PESERTA DIDIK')
            ->assertSee('Budi Santoso');

        // Test Excel Export
        $exportRes = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id . '/export');
        $exportRes->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_rekap_sts_supports_historical_academic_year(): void
    {
        // Create past academic year
        $pastYear = TahunAjaran::create([
            'nama' => '2024/2025',
            'tanggal_mulai' => now()->subYears(2)->startOfYear(),
            'tanggal_selesai' => now()->subYears(2)->endOfYear(),
            'is_aktif' => false,
        ]);

        // Enroll student in past year
        \App\Models\RiwayatKelasSiswa::create([
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $pastYear->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'status' => 'aktif',
        ]);

        // Historical grade
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $pastYear->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'sts_score' => 92.0,
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/erapor/recap?tahun_ajaran_id=' . $pastYear->id . '&semester=Ganjil&kelas=VII - Ibnu Taimiyah');

        $response->assertStatus(200)
            ->assertSee('2024/2025')
            ->assertSee('Arsip Masa Lalu')
            ->assertSee('Budi Santoso');

        // Test export for past year
        $exportRes = $this->actingAs($this->teacherUser)->get('/erapor/recap/export/VII - Ibnu Taimiyah?tahun_ajaran_id=' . $pastYear->id . '&semester=Ganjil');
        $exportRes->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_user_can_access_cetak_rapor_index_and_print_siswa(): void
    {
        // 1. Index page
        $indexRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah');
        $indexRes->assertStatus(200)
            ->assertSee('Cetak Rapor Siswa', false)
            ->assertSee('Budi Santoso');

        // 2. Individual student print
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printRes->assertStatus(200)
            ->assertSee('LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER')
            ->assertSee('SMK PGRI 5 SERPONG')
            ->assertSee('BUDI SANTOSO');
    }

    public function test_user_can_bulk_print_class_rapor(): void
    {
        $bulkRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=sts');
        $bulkRes->assertStatus(200)
            ->assertSee('Cetak Semua Rapor Kelas')
            ->assertSee('BUDI SANTOSO');
    }

    public function test_catatan_wali_kelas_can_be_viewed_and_saved(): void
    {
        // 1. View form
        $res = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/catatan-wali-kelas?kelas=VII - Ibnu Taimiyah');
        $res->assertStatus(200)
            ->assertSee('Form Input Catatan Wali Kelas')
            ->assertSee('Budi Santoso');

        // 2. Save note
        $postRes = $this->actingAs($this->teacherUser)->post('/erapor/cetak-rapor/catatan-wali-kelas', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan' => [
                $this->siswa->id => 'Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.',
            ],
        ]);

        $postRes->assertRedirect();
        $this->assertDatabaseHas('student_sts_summaries', [
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan_wali_kelas' => 'Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.',
        ]);

        // 3. Verify it is printed on student report card
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printRes->assertStatus(200)
            ->assertSee('Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.');
    }

    public function test_capaian_kompetensi_can_be_viewed_saved_and_auto_generated(): void
    {
        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Gedung Utama',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        // 1. View form
        $res = $this->actingAs($this->teacherUser)->get('/erapor/capaian-kompetensi?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);
        $res->assertStatus(200)
            ->assertSee('Input Capaian Kompetensi Mapel')
            ->assertSee('Budi Santoso');

        // 2. Save custom description
        $customText = 'Menunjukkan penguasaan sangat baik dalam materi Aljabar dan Persamaan Linier Satu Variabel.';
        $postRes = $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => $customText,
            ],
        ]);

        $postRes->assertRedirect();
        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'capaian_kompetensi' => $customText,
        ]);

        // 3. Verify print report (UAS) displays this custom description
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas');
        $printRes->assertStatus(200)
            ->assertSee($customText);

        // 4. Test auto-generate
        $summary = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();
        $summary->update(['sts_score' => 88.0]);

        $autoRes = $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/auto-generate', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);

        $autoRes->assertRedirect();
        $summary->refresh();

        $this->assertNotNull($summary->capaian_kompetensi);
        $this->assertStringContainsString('Matematika', $summary->capaian_kompetensi);
        $this->assertStringContainsString('telah mencapai tujuan pembelajaran', $summary->capaian_kompetensi);
    }

    public function test_capaian_kompetensi_is_strictly_scoped_to_assigned_teacher_schedule(): void
    {
        $mapel2 = MataPelajaran::create([
            'nama' => 'Bahasa Indonesia',
            'kode' => 'BIN',
        ]);

        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Gedung Utama',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        $guruBUser = User::factory()->create(['role' => 'guru']);
        $guruB = \App\Models\Guru::create([
            'user_id' => $guruBUser->id,
            'nip' => '198602022011012002',
            'nama_lengkap' => 'Guru Bahasa Indonesia',
            'jenis_kelamin' => 'P',
        ]);

        $guruCUser = User::factory()->create(['role' => 'guru']);
        \App\Models\Guru::create([
            'user_id' => $guruCUser->id,
            'nip' => '198703032012011003',
            'nama_lengkap' => 'Guru Tanpa Jadwal',
            'jenis_kelamin' => 'L',
        ]);

        $studentLainUser = User::factory()->create(['role' => 'siswa']);
        $siswaLain = Siswa::create([
            'user_id' => $studentLainUser->id,
            'nisn' => '0098765488',
            'nama_lengkap' => 'Siswa Kelas Sebelah',
            'jenis_kelamin' => 'P',
            'kelas' => 'VIII - Al Ghazali',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Guru A teaches Matematika in VII - Ibnu Taimiyah (and is Wali Kelas there)
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        // Guru B teaches Bahasa Indonesia in VII - Ibnu Taimiyah and Matematika in VIII - Al Ghazali
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $mapel2->id,
            'guru_id' => $guruB->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Selasa',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VIII - Al Ghazali',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $guruB->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Rabu',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        // 1. Guru A default view only shows their scheduled class & mapel, plus fixed header metadata
        $indexRes = $this->actingAs($this->teacherUser)->get('/erapor/capaian-kompetensi');
        $indexRes->assertStatus(200)
            ->assertSee('Matematika')
            ->assertSee('VII - Ibnu Taimiyah')
            ->assertSee('2026/2027')
            ->assertDontSee('Bahasa Indonesia')
            ->assertDontSee('VIII - Al Ghazali')
            ->assertDontSee('SMP Al Madinah');

        // 2. Guru A cannot GET Guru B's subject in same class (even if Wali Kelas)
        $this->actingAs($this->teacherUser)
            ->get('/erapor/capaian-kompetensi?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $mapel2->id)
            ->assertStatus(403);

        // 3. Guru A cannot GET their own subject name in Guru B's class
        $this->actingAs($this->teacherUser)
            ->get('/erapor/capaian-kompetensi?kelas=VIII - Al Ghazali&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(403);

        // 4. Guru A cannot POST /save for Guru B's schedule
        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $mapel2->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => 'Percobaan input ilegal mapel guru lain.',
            ],
        ])->assertStatus(403);

        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VIII - Al Ghazali',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $siswaLain->id => 'Percobaan input ilegal kelas guru lain.',
            ],
        ])->assertStatus(403);

        $this->assertDatabaseMissing('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $mapel2->id,
        ]);
        $this->assertDatabaseMissing('subject_grade_summaries', [
            'siswa_id' => $siswaLain->id,
            'mata_pelajaran_id' => $this->mapel->id,
        ]);

        // 5. Guru A cannot POST /auto-generate for Guru B's schedule
        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/auto-generate', [
            'mata_pelajaran_id' => $mapel2->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertStatus(403);

        // 6. Guru A cannot inject out-of-class student IDs even on their valid schedule POST
        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => 'Valid untuk siswa di kelas VII - Ibnu Taimiyah.',
                $siswaLain->id => 'Injected student from another class.',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'capaian_kompetensi' => 'Valid untuk siswa di kelas VII - Ibnu Taimiyah.',
        ]);
        $this->assertDatabaseMissing('subject_grade_summaries', [
            'siswa_id' => $siswaLain->id,
            'mata_pelajaran_id' => $this->mapel->id,
        ]);

        // 7. Guru C (0 schedules) gets clean empty state on GET, and 403 when targeting a class/mapel
        $this->actingAs($guruCUser)->get('/erapor/capaian-kompetensi')
            ->assertStatus(200)
            ->assertSee('Belum Ada Jadwal Mengajar Terdaftar');

        $this->actingAs($guruCUser)
            ->get('/erapor/capaian-kompetensi?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(403);

        $this->actingAs($guruCUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => 'Guru tanpa jadwal mencoba simpan.',
            ],
        ])->assertStatus(403);

        // 8. Admin / operator retains full access to all classes & subjects
        $this->actingAs($this->adminUser)
            ->get('/erapor/capaian-kompetensi?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $mapel2->id)
            ->assertStatus(200)
            ->assertSee('Bahasa Indonesia');

        $this->actingAs($this->adminUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VIII - Al Ghazali',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $siswaLain->id => 'Disimpan oleh Admin Sekolah.',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $siswaLain->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'capaian_kompetensi' => 'Disimpan oleh Admin Sekolah.',
        ]);
    }

    public function test_ai_catatan_wali_kelas_endpoint_returns_generated_note(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'openrouter.ai/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Ananda Budi Santoso menunjukkan semangat belajar yang sangat baik, teruslah istiqomah dan berakhlak mulia.'
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)->postJson('/erapor/cetak-rapor/catatan-wali-kelas/generate-ai', [
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'catatan' => 'Ananda Budi Santoso menunjukkan semangat belajar yang sangat baik, teruslah istiqomah dan berakhlak mulia.',
                'siswa_id' => $this->siswa->id,
            ]);
    }

    public function test_user_can_access_rapor_pengaturan(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/erapor/cetak-rapor/pengaturan');

        $response->assertStatus(200)
            ->assertSee('Pengaturan Format Rapor')
            ->assertSee('Format Lembar Rapor');
    }

    public function test_user_can_update_rapor_document_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/erapor/cetak-rapor/pengaturan', [
            'nama_kurikulum' => 'Kurikulum Khas Al-Madinah',
            'show_kurikulum' => '1',
            // Note: show_fase is omitted to disable Fase D
            'kota_pembagian' => 'Tangerang Selatan',
            'tanggal_rapor' => '2026-10-25',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'header_yayasan' => 'YAYASAN AL-MADINAH ISLAMIC EDUCATION',
            'header_sekolah' => 'SMP ISLAM PLUS AL-MADINAH',
        ]);

        $response->assertRedirect('/erapor/cetak-rapor/pengaturan');

        $settings = RaporSetting::instance();
        $this->assertEquals('Kurikulum Khas Al-Madinah', $settings->nama_kurikulum);
        $this->assertTrue($settings->show_kurikulum);
        $this->assertFalse($settings->show_fase);
        $this->assertEquals('Tangerang Selatan', $settings->kota_pembagian);
        $this->assertEquals('Dr. H. Ahmad Dahlan, M.Pd.', $settings->nama_kepala_sekolah);
    }

    public function test_user_can_update_homeroom_teacher_and_print_without_fase(): void
    {
        // 1. Setup custom document setting with show_fase = false
        $settings = RaporSetting::instance();
        $settings->update([
            'show_fase' => false,
            'show_kurikulum' => true,
            'nama_kurikulum' => 'Kurikulum Khas Al-Madinah',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'kota_pembagian' => 'Tangerang Selatan',
        ]);

        // 2. Setup custom homeroom for student's class (by Admin/Operator)
        $response = $this->actingAs($this->adminUser)->post('/erapor/cetak-rapor/pengaturan/homerooms', [
            'tahun_ajaran_id' => $this->tahun->id,
            'homerooms' => [
                'VII - Ibnu Taimiyah' => [
                    'guru_id' => $this->guru->id,
                    'nama_wali_kelas' => 'Ustadzah Siti Aminah, S.Pd.I',
                    'nip_wali_kelas' => '198802022015042001',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('class_homerooms', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'nama_wali_kelas' => 'Ustadzah Siti Aminah, S.Pd.I',
            'nip_wali_kelas' => '198802022015042001',
        ]);

        // 3. Print STS & UAS report cards and verify:
        // - "Fase / D" or "Kelas / Fase" does NOT appear
        // - Custom curriculum name appears
        // - Custom homeroom teacher appears on STS (2-col signature) and headmaster appears on UAS (3-col signature)
        $printStsRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printStsRes->assertStatus(200);
        $stsContent = $printStsRes->getContent();

        $this->assertStringNotContainsString('Fase D', $stsContent);
        $this->assertStringNotContainsString('Kelas / Fase', $stsContent);
        $this->assertStringContainsString('KURIKULUM KHAS AL-MADINAH', $stsContent);
        $this->assertStringContainsString('Ustadzah Siti Aminah, S.Pd.I', $stsContent);
        $this->assertStringContainsString('Tangerang Selatan', $stsContent);

        $printUasRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas');
        $printUasRes->assertStatus(200);
        $uasContent = $printUasRes->getContent();
        $this->assertStringContainsString('Dr. H. Ahmad Dahlan, M.Pd.', $uasContent);
    }

    public function test_end_to_end_guru_input_nilai_until_operator_walikelas_cetak_rapor_sts_and_uas(): void
    {
        // Create a second student in the same class to test class average & competition ranking
        $studentUser2 = User::factory()->create(['role' => 'siswa']);
        $siswa2 = Siswa::create([
            'user_id' => $studentUser2->id,
            'nisn' => '0098765499',
            'nama_lengkap' => 'Siti Rahmawati',
            'jenis_kelamin' => 'P',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Assign Jadwal for teacher so we also verify teacher name is NOT shown on UTS/STS print
        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Gedung Utama',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        $jadwal1 = \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        $jadwal2 = \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:30',
        ]);

        // Record SMART+ Attendance (AbsensiSiswa):
        // - 2 schedules on same date (2026-10-12) with status 'Sakit' -> should count as 1 Hari Sakit
        // - 1 date (2026-10-13) with status 'Izin' -> 1 Hari Izin
        // - 2 dates (2026-10-14 & 2026-10-15) with status 'Alfa' -> 2 Hari Tanpa Keterangan
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-12', 'status' => 'Sakit']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal2->id, 'tanggal' => '2026-10-12', 'status' => 'Sakit']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-13', 'status' => 'Izin']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-14', 'status' => 'Alfa']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal2->id, 'tanggal' => '2026-10-14', 'status' => 'Alfa']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-15', 'status' => 'Alfa']);

        // STEP 1: Guru opens Buku Nilai to auto-provision NH 1 (Tugas 1, Sumatif Harian 1) & Nilai Murni ASAT
        $bukuNilaiRes = $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);
        $bukuNilaiRes->assertStatus(200)
            ->assertSee('NILAI HARIAN 1')
            ->assertSee('Rata-rata')
            ->assertSee('Nilai Murni ASAT');

        $items = \App\Models\AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $this->assertCount(3, $items);

        // STEP 2: Guru inputs scores for Siswa 1 (Budi Santoso -> 90.0) & Siswa 2 (Siti Rahmawati -> 80.0)
        $tugasItem = $items->first(fn ($i) => $i->resolveWeightGroup() === 'tugas');
        $shItem = $items->first(fn ($i) => $i->resolveWeightGroup() === 'sumatif');
        $asatItem = $items->first(fn ($i) => $i->resolveWeightGroup() === 'asat');

        foreach ([
            [$this->siswa->id, $tugasItem->id, 90],
            [$this->siswa->id, $shItem->id, 90],
            [$this->siswa->id, $asatItem->id, 90],
            [$siswa2->id, $tugasItem->id, 80],
            [$siswa2->id, $shItem->id, 80],
            [$siswa2->id, $asatItem->id, 80],
        ] as [$sid, $iid, $sc]) {
            $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $sid,
                'assessment_item_id' => $iid,
                'score' => $sc,
            ])->assertStatus(200)->assertJson(['success' => true]);
        }

        // STEP 3: Guru inputs Capaian Kompetensi
        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => 'Sangat mahir dalam operasi aljabar dan aritmatika sosial.',
            ],
        ])->assertRedirect();

        // STEP 4: Wali Kelas inputs Catatan Wali Kelas
        $this->actingAs($this->teacherUser)->post('/erapor/cetak-rapor/catatan-wali-kelas', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan' => [
                $this->siswa->id => 'Pertahankan prestasi dan teruslah menjadi teladan di kelas.',
            ],
        ])->assertRedirect();

        // STEP 5: Operator / Wali Kelas configures Rapor Settings (Paper Size F4 & Show Ranking = true)
        $operatorUser = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($operatorUser)->post('/erapor/cetak-rapor/pengaturan', [
            'nama_kurikulum' => 'Kurikulum Merdeka',
            'show_kurikulum' => '1',
            'show_fase' => '1',
            'fase_text' => 'Fase D',
            'ukuran_kertas' => 'F4',
            'show_ranking' => '1',
            'kota_pembagian' => 'Tangerang Selatan',
            'tanggal_rapor' => '2026-10-25',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'header_yayasan' => 'YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)',
            'header_sekolah' => 'SMP AL - MADINAH ISLAMIC CENTER',
        ])->assertRedirect();

        // STEP 6: Operator / Wali Kelas prints Opsi 1: Rapor STS/UTS (Per Siswa)
        $stsPrintRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $stsPrintRes->assertStatus(200);
        $stsHtml = $stsPrintRes->getContent();

        // Verify F4 paper size CSS, 18-column headers, granular scores, no teacher name in mapel, summary & ranking
        $this->assertStringContainsString('215mm 330mm', $stsHtml);
        $this->assertStringContainsString('Ulangan Harian', $stsHtml);
        $this->assertStringContainsString('Tugas', $stsHtml);
        $this->assertStringContainsString('Praktek', $stsHtml);
        $this->assertStringContainsString('Projek', $stsHtml);
        $this->assertStringContainsString('Portofolio', $stsHtml);
        $this->assertStringContainsString('Nilai PTS', $stsHtml);
        $this->assertStringContainsString('Matematika', $stsHtml);
        $this->assertStringNotContainsString('(Guru Pengampu)', $stsHtml);
        $this->assertStringContainsString('Nilai Total PTS', $stsHtml);
        $this->assertStringContainsString('Nilai Rata-rata Siswa', $stsHtml);
        $this->assertStringContainsString('Nilai Rata-rata Kelas', $stsHtml);
        $this->assertStringContainsString('85.00', $stsHtml); // Class average of 90 and 80
        $this->assertStringContainsString('Ke-1 / 2', $stsHtml); // Rank 1 of 2 students
        $this->assertStringContainsString('1 Hari', $stsHtml); // 1 Hari Sakit & 1 Hari Izin (distinct dates)
        $this->assertStringContainsString('2 Hari', $stsHtml); // 2 Hari Tanpa Keterangan (Alfa)
        $this->assertStringContainsString('Wali Murid', $stsHtml);

        // STEP 7: Operator / Wali Kelas prints Opsi 2: Rapor ASAT/UAS (Per Siswa)
        $uasPrintRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $uasPrintRes->assertStatus(200);
        $uasHtml = $uasPrintRes->getContent();

        $this->assertStringContainsString('LAPORAN HASIL BELAJAR', $uasHtml);
        $this->assertStringContainsString('Nilai', $uasHtml);
        $this->assertStringContainsString('90.0', $uasHtml);
        $this->assertStringNotContainsString('Formatif', $uasHtml);
        $this->assertStringContainsString('Sangat mahir dalam operasi aljabar dan aritmatika sosial.', $uasHtml);
        $this->assertStringContainsString('Pertahankan prestasi dan teruslah menjadi teladan di kelas.', $uasHtml);
        $this->assertStringContainsString('Peringkat ke-1', $uasHtml);
        $this->assertStringContainsString('Dr. H. Ahmad Dahlan, M.Pd.', $uasHtml);

        // STEP 8: Bulk Class Print for both STS and UAS
        $bulkStsRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=sts&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $bulkStsRes->assertStatus(200)
            ->assertSee('BUDI SANTOSO')
            ->assertSee('SITI RAHMAWATI')
            ->assertSee('Nilai Total PTS')
            ->assertSee('Ke-1 / 2')
            ->assertSee('Ke-2 / 2');

        $bulkUasRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $bulkUasRes->assertStatus(200)
            ->assertSee('BUDI SANTOSO')
            ->assertSee('SITI RAHMAWATI')
            ->assertSee('Sangat mahir dalam operasi aljabar dan aritmatika sosial.');
    }

    public function test_role_access_and_sidebar_menus_for_guru_admin_sekolah_kepala_sekolah_and_super_admin(): void
    {
        $kepsekUser = User::factory()->create(['role' => 'kepala_sekolah']);
        $superAdminUser = User::factory()->create(['role' => 'super_admin']);

        // 1A. Verify Non-Wali Kelas Guru cannot see or access Cetak Rapor
        $nonWaliUser = User::factory()->create(['role' => 'guru']);
        \App\Models\Guru::create([
            'user_id' => $nonWaliUser->id,
            'nip' => '199001012020011009',
            'nama_lengkap' => 'Guru Mapel Biasa',
            'jenis_kelamin' => 'L',
        ]);

        $this->actingAs($nonWaliUser);
        $nonWaliMenus = collect(\App\Helpers\MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
            ->all();

        $this->assertContains('Buku Nilai Mapel', $nonWaliMenus);
        $this->assertNotContains('Cetak Rapor Siswa', $nonWaliMenus);
        $this->assertNotContains('Catatan Wali Kelas', $nonWaliMenus);

        $this->actingAs($nonWaliUser)->get('/erapor/buku-nilai')->assertStatus(200);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor')->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/catatan-wali-kelas')->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id)->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah')->assertStatus(403);

        // 1B. Verify Wali Kelas Guru (selected in Format & TTD Rapor) CAN see & access Cetak Rapor for their class
        $this->actingAs($this->teacherUser);
        $guruMenus = collect(\App\Helpers\MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
            ->all();

        $this->assertContains('Buku Nilai Mapel', $guruMenus);
        $this->assertContains('Cetak Rapor Siswa', $guruMenus);
        $this->assertNotContains('Dashboard E-Rapor', $guruMenus);
        $this->assertNotContains('Integrasi CBT', $guruMenus);
        $this->assertNotContains('Format & TTD Rapor', $guruMenus);
        $this->assertNotContains('Buku Induk & Transkrip', $guruMenus);

        // Wali Kelas cannot print a different class they are not assigned to
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/kelas/IX - Al-Khawarizmi')->assertStatus(403);

        // Guru can access Buku Nilai & Cetak Rapor (STS & UAS), and UI hides Finalize & Format buttons
        $this->actingAs($this->teacherUser)->get('/erapor/buku-nilai')->assertStatus(200);
        $cetakGuruRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah');
        $cetakGuruRes->assertStatus(200)
            ->assertDontSee('Format & TTD Rapor')
            ->assertDontSee('Buka Leger Raport untuk Finalisasi');

        $rekapGuruRes = $this->actingAs($this->teacherUser)->get('/erapor/recap?kelas=VII - Ibnu Taimiyah');
        $rekapGuruRes->assertStatus(200)
            ->assertDontSee('Finalisasi Rapor Kelas')
            ->assertSee('Status: Draf (Menunggu Finalisasi Operator)');

        // Guru gets 403 on restricted routes
        $this->actingAs($this->teacherUser)->get('/erapor')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cbt')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/buku-induk')->assertStatus(403);
        $this->actingAs($this->teacherUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah', [
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertStatus(403);

        // 2. Verify Admin Sekolah, Kepala Sekolah, and Super Admin have full access to all 5 restricted features
        foreach ([$this->adminUser, $kepsekUser, $superAdminUser] as $privilegedUser) {
            $this->actingAs($privilegedUser);
            $menus = collect(\App\Helpers\MenuHelper::getMenuGroups())
                ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
                ->all();

            $this->assertContains('Dashboard E-Rapor', $menus);
            $this->assertContains('Integrasi CBT', $menus);
            $this->assertContains('Format & TTD Rapor', $menus);
            $this->assertContains('Buku Induk & Transkrip', $menus);

            $this->actingAs($privilegedUser)->get('/erapor')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/cbt')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/buku-induk')->assertStatus(200);
        }

        // Verify Finalisasi Rapor works for Admin Sekolah / Kepala Sekolah / Super Admin
        $finalizeRes = $this->actingAs($this->adminUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah', [
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);
        $finalizeRes->assertRedirect();
        $this->assertDatabaseHas('report_finalizations', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'status' => 'final',
        ]);
    }

    public function test_asat_grading_formula_nh1_nh2_sh_avg_and_final_score_with_one_decimal(): void
    {
        // Open Buku Nilai to auto-provision NH 1 (Tugas 1, Sumatif Harian 1) & Nilai Murni ASAT
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(200);

        // Add Tugas 2 & UH 1 inside NH 1
        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/item', [
            'weight_group' => 'tugas',
            'daily_block' => 1,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'title' => 'Tugas 2',
        ])->assertRedirect();

        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/item', [
            'weight_group' => 'sumatif',
            'daily_block' => 1,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'title' => 'Ujian Harian 1',
        ])->assertRedirect();

        // Add optional NH 2 block
        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/daily-block', [
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertRedirect();

        $items = \App\Models\AssessmentItem::where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $nh1Tugas1 = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'tugas' && $i->title === 'Tugas 1');
        $nh1Tugas2 = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'tugas' && $i->title === 'Tugas 2');
        $nh1Sh1 = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'sumatif' && $i->title === 'Sumatif Harian 1');
        $nh1Uh1 = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'sumatif' && $i->title === 'Ujian Harian 1');
        $nh2Tugas1 = $items->first(fn ($i) => $i->daily_block === 2 && $i->weight_group === 'tugas');
        $nh2Sh1 = $items->first(fn ($i) => $i->daily_block === 2 && $i->weight_group === 'sumatif');
        $asatItem = $items->first(fn ($i) => $i->weight_group === 'asat');

        // NH 1: Tugas (80, 90 -> 85.0), Sumatif/UH (70, 80 -> 75.0) => 40%*85 + 60%*75 = 79.0
        // NH 2: Tugas (85.0), Sumatif/UH (95.0) => 40%*85 + 60%*95 = 91.0
        // SH avg = (70 + 80 + 95) / 3 = 81.7
        // ASAT = 85.0 => Final = (79.0 + 91.0 + 85.0) / 3 = 85.0
        foreach ([
            [$nh1Tugas1->id, 80],
            [$nh1Tugas2->id, 90],
            [$nh1Sh1->id, 70],
            [$nh1Uh1->id, 80],
            [$nh2Tugas1->id, 85],
            [$nh2Sh1->id, 95],
            [$asatItem->id, 85],
        ] as [$itemId, $score]) {
            $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $itemId,
                'score' => $score,
            ])->assertStatus(200);
        }

        $summary = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();

        $this->assertNotNull($summary);
        $this->assertEquals(79.0, $summary->daily_scores['1']['final']);
        $this->assertEquals(91.0, $summary->daily_scores['2']['final']);
        $this->assertEquals(81.7, $summary->sh_avg);
        $this->assertEquals(85.0, $summary->asat_score);
        $this->assertEquals(85.0, $summary->final_score);

        // Verify clean ASAT/UAS print view renders Nilai Akhir (85.0) + Capaian Kompetensi without Formatif column
        $printRes = $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas');
        $printRes->assertStatus(200)
            ->assertSee('85.0')
            ->assertSee('Capaian Pembelajaran')
            ->assertDontSee('Formatif');
    }

    public function test_uas_print_matches_two_page_format_with_ekskul_keputusan_and_fase(): void
    {
        $bd = \App\Models\Jurusan::create([
            'kode' => 'BD',
            'singkatan' => 'BD',
            'nama' => 'Bisnis Digital',
            'is_aktif' => true,
        ]);

        $rombelXiBd = \App\Models\Rombel::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'nama' => 'XI BD',
            'tingkat' => 'XI',
            'jurusan_id' => $bd->id,
            'kompetensi_keahlian' => 'Bisnis Digital',
            'is_aktif' => true,
        ]);

        $siswaBd = Siswa::create([
            'nisn' => '0088123456',
            'nipd' => '2425.152',
            'nama_lengkap' => 'Sifa Faujiah',
            'jenis_kelamin' => 'P',
            'kelas' => 'XI BD',
            'rombel_id' => $rombelXiBd->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'status_siswa' => 'aktif',
        ]);

        \App\Models\ClassHomeroom::create([
            'kelas' => 'XI BD',
            'tahun_ajaran_id' => $this->tahun->id,
            'guru_id' => $this->guru->id,
            'nama_wali_kelas' => 'M. Afifuddin Nur, S.Pd',
            'nip_wali_kelas' => '-',
        ]);

        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $siswaBd->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'kelas' => 'XI BD',
            'asat_score' => 83.0,
            'final_score' => 83.0,
            'capaian_kompetensi' => 'Menunjukan penguasaan yang baik dalam Bidang kajian Analisis Data.',
            'status' => 'final',
        ]);

        $this->actingAs($this->adminUser)->post(route('erapor.cetak.save-catatan-wali'), [
            'kelas' => 'XI BD',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'catatan' => [
                $siswaBd->id => 'Ayo, maksimalkan potensimu di semester depan!',
            ],
            'ekskul' => [
                $siswaBd->id => [
                    ['nama' => 'Tari', 'keterangan' => 'A'],
                    ['nama' => 'Paskibra', 'keterangan' => 'A'],
                    ['nama' => '', 'keterangan' => ''],
                ],
            ],
            'keputusan_status' => [
                $siswaBd->id => '',
            ],
            'keputusan_kelas' => [
                $siswaBd->id => '',
            ],
        ])->assertRedirect();

        $res = $this->actingAs($this->adminUser)->get(route('erapor.cetak.siswa', [
            'siswa' => $siswaBd->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'jenis_rapor' => 'uas',
        ]));

        $res->assertStatus(200)
            ->assertSee('LAPORAN HASIL BELAJAR')
            ->assertSee('A. Nilai Akademik')
            ->assertSee('Capaian Pembelajaran')
            ->assertSee('Bisnis Digital')
            ->assertSee('Fase')
            ->assertSee('F')
            ->assertSee('uas-page-2')
            ->assertSee('B. Ekstrakurikuler')
            ->assertSee('Tari')
            ->assertSee('Paskibra')
            ->assertSee('C. Ketidakhadiran')
            ->assertSee('D. Catatan Wali Kelas')
            ->assertSee('Ayo, maksimalkan potensimu di semester depan!')
            ->assertSee('Keputusan :')
            ->assertSee('Naik ke kelas')
            ->assertSee('12 (Dua belas)')
            ->assertSee('Tinggal di kelas')
            ->assertSee('Orang Tua/Wali,');
    }

    public function test_cetak_rapor_filter_shows_only_uts_on_ganjil_and_only_uas_on_genap_and_syncs_catatan_to_active_archive(): void
    {
        // Default Ganjil filter -> shows only Rapor UTS
        $ganjilRes = $this->actingAs($this->adminUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah&tahun_ajaran_id=' . $this->tahun->id);
        $ganjilRes->assertStatus(200)
            ->assertSee('Cetak Semua Rapor UTS')
            ->assertSee('Cetak Rapor UTS')
            ->assertDontSee('Cetak Semua Rapor UAS')
            ->assertDontSee('Cetak Rapor UAS');

        // Genap filter -> shows only Rapor UAS
        $genapRes = $this->actingAs($this->adminUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah&semester=Genap&tahun_ajaran_id=' . $this->tahun->id);
        $genapRes->assertStatus(200)
            ->assertSee('Cetak Semua Rapor UAS')
            ->assertSee('Cetak Rapor UAS')
            ->assertDontSee('Cetak Semua Rapor UTS')
            ->assertDontSee('Cetak Rapor UTS');

        // Archive class for active year Genap first
        $this->actingAs($this->adminUser)->post(route('erapor.cetak.archive-class'), [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'jenis_rapor' => 'uas',
        ])->assertRedirect();

        // Update Catatan Wali Kelas & Ekskul AFTER archive was frozen (active year + active student)
        $this->actingAs($this->teacherUser)->post(route('erapor.cetak.save-catatan-wali'), [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
            'catatan' => [
                $this->siswa->id => 'Catatan terbaru langsung tersinkron ke arsip aktif.',
            ],
            'ekskul' => [
                $this->siswa->id => [
                    ['nama' => 'Robotik', 'keterangan' => 'A'],
                ],
            ],
        ])->assertRedirect();

        // Print UAS -> must reflect the newly saved note & ekstrakurikuler immediately
        $uasPrint = $this->actingAs($this->adminUser)->get(route('erapor.cetak.siswa', [
            'siswa' => $this->siswa->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Genap',
        ]));
        $uasPrint->assertStatus(200)
            ->assertSee('LAPORAN HASIL BELAJAR')
            ->assertSee('Catatan terbaru langsung tersinkron ke arsip aktif.')
            ->assertSee('Robotik');
    }

    public function test_partial_nh_block_keeps_40_60_weights_and_empty_nh2_is_ignored(): void
    {
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/daily-block', [
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertRedirect();

        $items = \App\Models\AssessmentItem::where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $nh1Tugas1 = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'tugas');
        $asatItem = $items->first(fn ($i) => $i->weight_group === 'asat');

        // Fill only Tugas in NH 1 (80), leave Sumatif empty => 40% * 80 + 60% * 0 = 32.0
        // Leave NH 2 completely empty => ignored in AVERAGE
        // ASAT = 90 => Final = (32.0 + 90.0) / 2 = 61.0
        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $nh1Tugas1->id,
            'score' => 80,
        ])->assertStatus(200);

        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $asatItem->id,
            'score' => 90,
        ])->assertStatus(200);

        $summary = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();

        $this->assertEquals(32.0, $summary->daily_scores['1']['final']);
        $this->assertNull($summary->daily_scores['2']['final']);
        $this->assertEquals(61.0, $summary->final_score);
    }

    public function test_score_range_descriptions_for_asat_report(): void
    {
        $descA = \App\Http\Controllers\Erapor\CetakRaporController::generateDeskripsi('Matematika', 92.5, 'A');
        $descB = \App\Http\Controllers\Erapor\CetakRaporController::generateDeskripsi('Matematika', 83.0, 'B');
        $descC = \App\Http\Controllers\Erapor\CetakRaporController::generateDeskripsi('Matematika', 74.5, 'C');
        $descD = \App\Http\Controllers\Erapor\CetakRaporController::generateDeskripsi('Matematika', 62.0, 'D');

        $this->assertStringContainsString('sangat baik dengan pemahaman materi Matematika', $descA);
        $this->assertStringContainsString('baik dengan pemahaman materi Matematika', $descB);
        $this->assertStringContainsString('cukup baik pada materi Matematika', $descC);
        $this->assertStringContainsString('Memerlukan bimbingan', $descD);
    }

    public function test_teacher_can_add_and_delete_optional_daily_block_and_recalculates_grades(): void
    {
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        // Add NH 2
        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/daily-block', [
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertRedirect();

        $items = \App\Models\AssessmentItem::where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $nh1Tugas = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'tugas');
        $nh1Sh = $items->first(fn ($i) => $i->daily_block === 1 && $i->weight_group === 'sumatif');
        $nh2Tugas = $items->first(fn ($i) => $i->daily_block === 2 && $i->weight_group === 'tugas');
        $nh2Sh = $items->first(fn ($i) => $i->daily_block === 2 && $i->weight_group === 'sumatif');
        $asatItem = $items->first(fn ($i) => $i->weight_group === 'asat');

        foreach ([
            [$nh1Tugas->id, 80],
            [$nh1Sh->id, 80],
            [$nh2Tugas->id, 100],
            [$nh2Sh->id, 100],
            [$asatItem->id, 90],
        ] as [$iid, $sc]) {
            $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $iid,
                'score' => $sc,
            ])->assertStatus(200);
        }

        // Before deleting NH 2: (80 + 100 + 90) / 3 = 90.0
        $summaryBefore = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();
        $this->assertEquals(90.0, $summaryBefore->final_score);

        // Cannot delete NH 1
        $this->actingAs($this->teacherUser)->delete('/erapor/buku-nilai/daily-block/1', [
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertRedirect()->assertSessionHas('error');

        // Delete NH 2 => recalculates to (80 + 90) / 2 = 85.0
        $this->actingAs($this->teacherUser)->delete('/erapor/buku-nilai/daily-block/2', [
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('assessment_items', [
            'mata_pelajaran_id' => $this->mapel->id,
            'daily_block' => 2,
        ]);

        $summaryAfter = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();
        $this->assertEquals(85.0, $summaryAfter->final_score);
    }

    public function test_buku_nilai_filter_and_mutations_are_strictly_scoped_to_teacher_schedule(): void
    {
        $mapel2 = MataPelajaran::create([
            'nama' => 'Bahasa Indonesia',
            'kode' => 'BIN',
        ]);

        $lokasi = \App\Models\Lokasi::first();

        $guruBUser = User::factory()->create(['role' => 'guru']);
        $guruB = \App\Models\Guru::create([
            'user_id' => $guruBUser->id,
            'nip' => '198602022011012005',
            'nama_lengkap' => 'Guru Mapel Lain',
            'jenis_kelamin' => 'P',
        ]);

        $guruCUser = User::factory()->create(['role' => 'guru']);
        \App\Models\Guru::create([
            'user_id' => $guruCUser->id,
            'nip' => '198703032012011006',
            'nama_lengkap' => 'Guru Tanpa Jadwal Buku Nilai',
            'jenis_kelamin' => 'L',
        ]);

        $studentLainUser = User::factory()->create(['role' => 'siswa']);
        $siswaLain = Siswa::create([
            'user_id' => $studentLainUser->id,
            'nisn' => '0098765477',
            'nama_lengkap' => 'Siswa Kelas Sebelah Buku Nilai',
            'jenis_kelamin' => 'P',
            'kelas' => 'VIII - Al Ghazali',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Ensure VIII - Al Ghazali also exists in Rombel so admin sees it
        \App\Models\Rombel::create([
            'nama' => 'VIII - Al Ghazali',
            'tingkat' => 'VIII',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Guru B teaches Bahasa Indonesia in VII - Ibnu Taimiyah and Matematika in VIII - Al Ghazali
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $mapel2->id,
            'guru_id' => $guruB->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Selasa',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);
        \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VIII - Al Ghazali',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $guruB->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Rabu',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        // 1. Guru A GET /erapor/buku-nilai ONLY renders their scheduled class & subject
        $indexRes = $this->actingAs($this->teacherUser)->get('/erapor/buku-nilai');
        $indexRes->assertStatus(200)
            ->assertSee('VII - Ibnu Taimiyah')
            ->assertSee('Matematika')
            ->assertDontSee('VIII - Al Ghazali')
            ->assertDontSee('Bahasa Indonesia');

        // 2. Guru A cannot GET Guru B's (VII - Ibnu Taimiyah, Bahasa Indonesia) or (VIII - Al Ghazali, Matematika)
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $mapel2->id)
            ->assertStatus(403);

        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VIII - Al Ghazali&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(403);

        // Create AssessmentItems for Matematika and Bahasa Indonesia via admin/Guru B
        $this->actingAs($guruBUser)->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $mapel2->id)->assertStatus(200);
        $this->actingAs($guruBUser)->get('/erapor/buku-nilai?kelas=VIII - Al Ghazali&mata_pelajaran_id=' . $this->mapel->id)->assertStatus(200);

        $binItem = \App\Models\AssessmentItem::where('mata_pelajaran_id', $mapel2->id)->firstOrFail();
        $mtkItem = \App\Models\AssessmentItem::where('mata_pelajaran_id', $this->mapel->id)->firstOrFail();

        // 3. Guru A cannot update-score on Guru B's student ($siswaLain in VIII - Al Ghazali) or Bahasa Indonesia item
        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $siswaLain->id,
            'assessment_item_id' => $mtkItem->id,
            'score' => 88,
        ])->assertStatus(403);

        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $binItem->id,
            'score' => 77,
        ])->assertStatus(403);

        $this->assertDatabaseMissing('student_assessment_scores', [
            'siswa_id' => $siswaLain->id,
            'assessment_item_id' => $mtkItem->id,
        ]);
        $this->assertDatabaseMissing('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $binItem->id,
        ]);

        // 4. Guru A cannot storeItem, storeDailyBlock, or syncFromCbt on Bahasa Indonesia
        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/item', [
            'weight_group' => 'tugas',
            'daily_block' => 1,
            'mata_pelajaran_id' => $mapel2->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'title' => 'Tugas Ilegal BIN',
        ])->assertStatus(403);

        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/daily-block', [
            'mata_pelajaran_id' => $mapel2->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
        ])->assertStatus(403);

        $this->actingAs($this->teacherUser)->post('/erapor/buku-nilai/sync-cbt', [
            'mata_pelajaran_id' => $mapel2->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
        ])->assertStatus(403);

        // 5. Guru C (0 schedules) sees empty state cleanly on GET, and gets 403 when targeting a class/mapel
        $this->actingAs($guruCUser)->get('/erapor/buku-nilai')
            ->assertStatus(200)
            ->assertSee('Belum Ada Jadwal Mengajar Terdaftar');

        $this->actingAs($guruCUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(403);

        // 6. Admin retains unrestricted access to all classes & subjects
        $this->actingAs($this->adminUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $mapel2->id)
            ->assertStatus(200)
            ->assertSee('Bahasa Indonesia');

        $this->actingAs($this->adminUser)
            ->get('/erapor/buku-nilai?kelas=VIII - Al Ghazali&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(200)
            ->assertSee('Siswa Kelas Sebelah Buku Nilai');
    }
}


