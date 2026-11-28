<?php

use App\Models\ClassHomeroom;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporArchive;
use App\Models\RaporSetting;
use App\Models\RiwayatKelasSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\QrCodeSvgService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('offline qr svg service generates valid svg markup across URL lengths', function () {
    $shortSvg = QrCodeSvgService::svg('https://smart.smkpgri5serpong.sch.id/verifikasi-rapor/abc', 74);
    $longSvg = QrCodeSvgService::svg('https://smart.smkpgri5serpong.sch.id/verifikasi-rapor/550e8400-e29b-41d4-a716-446655440000', 74);

    expect($shortSvg)->toContain('<svg')->toContain('<path')->toContain('width="74"');
    expect($longSvg)->toContain('<svg')->toContain('<path')->toContain('</svg>');
});

test('alumni rapor archive freezes immutable snapshot before graduation and survives future settings, Kepsek, and grade changes', function () {
    $admin = User::factory()->create(['role' => 'admin_sekolah', 'is_active' => true]);
    $kepsek = User::factory()->create(['role' => 'kepala_sekolah', 'name' => 'Encum Sumiati, S.Pd']);
    $waliUser = User::factory()->create(['role' => 'guru', 'name' => 'Didiek Hery Priyantono, SE']);
    $guru = Guru::create([
        'user_id' => $waliUser->id,
        'nip' => '202607010006',
        'nama_lengkap' => 'Didiek Hery Priyantono, SE',
        'jenis_kelamin' => 'L',
    ]);

    PengaturanSekolah::create([
        'nama_sekolah' => 'SMKS PGRI 5 Serpong',
        'jam_masuk' => '12:30',
        'jam_pulang' => '17:20',
    ]);

    $ta2026 = TahunAjaran::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_aktif' => true,
    ]);

    $ta2027 = TahunAjaran::create([
        'nama' => '2027/2028',
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2028-06-30',
        'is_aktif' => false,
    ]);

    $rombel = Rombel::create([
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'nama' => 'XII AK',
        'tingkat' => 'XII',
        'kompetensi_keahlian' => 'Akuntansi dan Keuangan Lembaga',
        'wali_kelas_guru_id' => $guru->id,
        'is_aktif' => true,
    ]);

    ClassHomeroom::create([
        'kelas' => 'XII AK',
        'tahun_ajaran_id' => $ta2026->id,
        'guru_id' => $guru->id,
        'nama_wali_kelas' => $guru->nama_lengkap,
        'nip_wali_kelas' => $guru->nip,
    ]);

    RaporSetting::instance()->update([
        'nama_kepala_sekolah' => 'Encum Sumiati, S.Pd',
        'nip_kepala_sekolah' => '197001012000012001',
        'kota_pembagian' => 'Kota Tangerang Selatan',
        'header_sekolah' => 'SMKS PGRI 5 SERPONG',
    ]);

    $studentUser = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
    $siswa = Siswa::create([
        'user_id' => $studentUser->id,
        'nipd' => '2425.001',
        'nisn' => '0091234567',
        'nama_lengkap' => 'Siti Aulia',
        'jenis_kelamin' => 'P',
        'kelas' => 'XII AK',
        'rombel_id' => $rombel->id,
        'tahun_ajaran_id' => $ta2026->id,
        'status_siswa' => 'aktif',
    ]);

    RiwayatKelasSiswa::create([
        'siswa_id' => $siswa->id,
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'rombel_id' => $rombel->id,
        'kelas' => 'XII AK',
        'status' => 'aktif',
    ]);

    $mapel = MataPelajaran::create([
        'kode' => 'AKL-B1',
        'nama' => 'Akuntansi Keuangan',
        'kkm' => 75,
    ]);

    SubjectGradeSummary::create([
        'siswa_id' => $siswa->id,
        'mata_pelajaran_id' => $mapel->id,
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'kelas' => 'XII AK',
        'uh_avg' => 90,
        'tugas_avg' => 92,
        'sts_score' => 91,
        'final_score' => 91,
        'capaian_kompetensi' => 'Sangat kompeten menyusun laporan keuangan perusahaan jasa dan dagang.',
        'status' => 'siap_ditinjau',
    ]);

    StudentStsSummary::create([
        'siswa_id' => $siswa->id,
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'total_score' => 91,
        'average_score' => 91,
        'rank' => 1,
        'completed_subjects_count' => 1,
        'catatan_wali_kelas' => 'Pertahankan prestasi akuntansi hingga kelulusan.',
    ]);

    // 1. Freeze / Archive Rapor Kelas
    $archiveRes = $this->actingAs($admin)->post(route('erapor.cetak.archive-class'), [
        'kelas' => 'XII AK',
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'jenis_rapor' => 'all',
    ]);
    $archiveRes->assertRedirect()->assertSessionHas('success');

    $uasArchive = RaporArchive::where('siswa_id', $siswa->id)
        ->where('tahun_ajaran_id', $ta2026->id)
        ->where('semester', 'Ganjil')
        ->where('jenis_rapor', 'uas')
        ->first();

    expect($uasArchive)->not->toBeNull();
    expect($uasArchive->kelas)->toBe('XII AK');
    expect($uasArchive->version)->toBe(1);
    expect($uasArchive->verifyIntegrity())->toBeTrue();

    // 2. Graduate student via Kenaikan Kelas Massal -> Alumni 2027
    $gradRes = $this->actingAs($admin)->post(route('admin.siswa.proses-kenaikan-kelas'), [
        'tahun_ajaran_tujuan' => $ta2027->id,
        'tahun_lulus' => 2027,
        'tanggal_kelulusan' => '2027-05-05',
        'kelas_tujuan' => ['XII AK' => 'Alumni'],
        'siswa_naik' => ['XII AK' => [$siswa->id]],
    ]);
    $gradRes->assertRedirect(route('admin.siswa.index'));

    $siswa->refresh();
    expect($siswa->status_siswa)->toBe('lulus');
    expect($siswa->tahun_lulus)->toBe(2027);
    expect($siswa->tahun_ajaran_lulus_id)->toBe($ta2026->id);
    expect($studentUser->fresh()->is_active)->toBeFalse();

    // Ensure no generic "Alumni" placeholder polluted riwayat_kelas_siswa
    expect(RiwayatKelasSiswa::where('siswa_id', $siswa->id)->where('kelas', 'Alumni')->exists())->toBeFalse();

    // 3. Time-travel mutation: Year 2029 changes Kepsek, city, and live score
    RaporSetting::instance()->update([
        'nama_kepala_sekolah' => 'Kepala Sekolah Baru Tahun 2029',
        'kota_pembagian' => 'Jakarta',
    ]);
    SubjectGradeSummary::where('siswa_id', $siswa->id)->update(['final_score' => 55, 'sts_score' => 55]);

    // 4. Reprint Alumni's archived 2026/2027 Ganjil UAS Rapor -> MUST show original XII AK, 91.0, Encum Sumiati, and QR!
    $printRes = $this->actingAs($admin)->get(route('erapor.cetak.siswa', [
        'siswa' => $siswa->id,
        'tahun_ajaran_id' => $ta2026->id,
        'semester' => 'Ganjil',
        'jenis_rapor' => 'uas',
    ]));
    $printRes->assertStatus(200)
        ->assertSee('XII AK')
        ->assertSee('Encum Sumiati, S.Pd')
        ->assertSee('91.0')
        ->assertSee($uasArchive->archive_number)
        ->assertSee('<svg', false)
        ->assertDontSee('Kepala Sekolah Baru Tahun 2029');

    expect($uasArchive->fresh()->reprint_count)->toBe(1);

    // 5. Buku Induk filters by Angkatan Lulus (2027) & shows semester reprint buttons
    $bukuIndukRes = $this->actingAs($admin)->get(route('erapor.buku-induk.index', ['tahun_lulus' => 2027]));
    $bukuIndukRes->assertStatus(200)
        ->assertSee('Siti Aulia')
        ->assertSee('Alumni 2027');

    $detailRes = $this->actingAs($admin)->get(route('erapor.buku-induk.show', $siswa));
    $detailRes->assertStatus(200)
        ->assertSee('XII AK')
        ->assertSee('Arsip UAS v1')
        ->assertSee('Cetak UAS');

    // 6. Public QR Verification endpoint (no auth needed)
    auth()->logout();
    $verifyRes = $this->get(route('rapor.verify', $uasArchive->verification_token));
    $verifyRes->assertStatus(200)
        ->assertSee('Dokumen Rapor Digital Terverifikasi Asli')
        ->assertSee('Siti Aulia')
        ->assertSee('XII AK')
        ->assertSee($uasArchive->archive_number);

    $badVerifyRes = $this->get(route('rapor.verify', '00000000-0000-0000-0000-000000000000'));
    $badVerifyRes->assertStatus(404)
        ->assertSee('Arsip Rapor Tidak Ditemukan');
});

test('RaporBukuIndukDemoSeeder seeds active and multi-cohort alumni transcripts chronologically and idempotently', function () {
    $admin = User::factory()->create(['role' => 'admin_sekolah', 'is_active' => true]);
    $ta2026 = TahunAjaran::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_aktif' => true,
    ]);
    // Out-of-order ID check: 2025/2026 & 2024/2025 created before 2022/2023 & 2023/2024
    TahunAjaran::create(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30', 'is_aktif' => false]);
    TahunAjaran::create(['nama' => '2024/2025', 'tanggal_mulai' => '2024-07-01', 'tanggal_selesai' => '2025-06-30', 'is_aktif' => false]);

    $akl = \App\Models\Jurusan::create(['kode' => 'AKL', 'singkatan' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'is_aktif' => true]);
    $mplb = \App\Models\Jurusan::create(['kode' => 'MPLB', 'singkatan' => 'MP', 'nama' => 'Manajemen Perkantoran dan Layanan Bisnis', 'is_aktif' => true]);
    $bd = \App\Models\Jurusan::create(['kode' => 'BD', 'singkatan' => 'BD', 'nama' => 'Bisnis Digital', 'is_aktif' => true]);

    foreach ([
        ['X AK', 'X', $akl],
        ['XI AK 1', 'XI', $akl],
        ['XII AK', 'XII', $akl],
        ['X MP 1', 'X', $mplb],
        ['XI MP 1', 'XI', $mplb],
        ['XII MP 1', 'XII', $mplb],
        ['X BD', 'X', $bd],
        ['XI BD', 'XI', $bd],
        ['XII BD', 'XII', $bd],
    ] as [$rName, $tingkat, $jur]) {
        Rombel::create([
            'tahun_ajaran_id' => $ta2026->id,
            'semester' => 'Ganjil',
            'nama' => $rName,
            'tingkat' => $tingkat,
            'jurusan_id' => $jur->id,
            'kompetensi_keahlian' => $jur->nama,
            'is_aktif' => true,
        ]);
    }

    Siswa::create([
        'nisn' => '0099900011',
        'nama_lengkap' => 'Budi Aktif XI MP',
        'jenis_kelamin' => 'L',
        'kelas' => 'XI MP 1',
        'tahun_ajaran_id' => $ta2026->id,
        'status_siswa' => 'aktif',
    ]);

    $this->seed(\Database\Seeders\RaporBukuIndukDemoSeeder::class);
    // Run a second time to verify idempotency
    $this->seed(\Database\Seeders\RaporBukuIndukDemoSeeder::class);

    $alumni2025 = Siswa::where('nisn', '0061002025')->firstOrFail();
    $alumni2026 = Siswa::where('nisn', '0071002026')->firstOrFail();

    expect(RiwayatKelasSiswa::where('siswa_id', $alumni2025->id)->count())->toBe(6);
    expect(RaporArchive::where('siswa_id', $alumni2025->id)->count())->toBe(6);
    expect(RaporArchive::where('siswa_id', $alumni2026->id)->count())->toBe(6);

    // Buku Induk detail shows chronological semesters (2022/2023 -> 2023/2024 -> 2024/2025)
    $detailRes = $this->actingAs($admin)->get(route('erapor.buku-induk.show', $alumni2025));
    $detailRes->assertStatus(200)
        ->assertSee('Dimas Aditya Mahendra')
        ->assertSee('DN-02/M-SMK/25/0009811')
        ->assertSee('2022/2023')
        ->assertSee('2023/2024')
        ->assertSee('2024/2025');

    $content = $detailRes->getContent();
    expect(strpos($content, '2022/2023 (Sem. Ganjil)'))->toBeLessThan(strpos($content, '2024/2025 (Sem. Genap)'));

    // Print & Excel transcript succeed
    $this->actingAs($admin)->get(route('erapor.buku-induk.print', $alumni2025))
        ->assertStatus(200)
        ->assertSee('DIMAS ADITYA MAHENDRA');
    $this->actingAs($admin)->get(route('erapor.buku-induk.export', $alumni2025))
        ->assertStatus(200);
});
