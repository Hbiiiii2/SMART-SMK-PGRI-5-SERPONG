<?php

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can CRUD jurusan, cascade update to rombels, and block delete when linked', function () {
    $admin = User::factory()->create(['role' => 'admin_sekolah', 'is_active' => true]);
    $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
    $ta = TahunAjaran::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_aktif' => true,
    ]);

    $this->actingAs($guru)->get(route('admin.jurusan.index'))->assertStatus(403);

    $this->actingAs($admin)->post(route('admin.jurusan.store'), [
        'kode' => 'akl',
        'singkatan' => 'ak',
        'nama' => 'Akuntansi Keuangan',
        'is_aktif' => 1,
    ])->assertRedirect(route('admin.jurusan.index'));

    $jurusan = Jurusan::where('kode', 'AKL')->firstOrFail();
    expect($jurusan->singkatan)->toBe('AK');

    // Create Rombel selecting jurusan_id
    $this->actingAs($admin)->post(route('admin.kelas.store'), [
        'nama' => 'X AK',
        'tahun_ajaran_id' => $ta->id,
        'semester' => 'Ganjil',
        'jurusan_id' => $jurusan->id,
        'is_aktif' => 1,
    ])->assertRedirect(route('admin.kelas.index'));

    $rombel = Rombel::where('nama', 'X AK')->firstOrFail();
    expect($rombel->jurusan_id)->toBe($jurusan->id);
    expect($rombel->kompetensi_keahlian)->toBe('Akuntansi Keuangan');

    // Edit Jurusan cascades nama to Rombel.kompetensi_keahlian
    $this->actingAs($admin)->put(route('admin.jurusan.update', $jurusan), [
        'kode' => 'AKL',
        'singkatan' => 'AK',
        'nama' => 'Akuntansi dan Keuangan Lembaga',
        'is_aktif' => 1,
    ])->assertRedirect(route('admin.jurusan.index'));

    expect($rombel->fresh()->kompetensi_keahlian)->toBe('Akuntansi dan Keuangan Lembaga');

    // Delete linked Jurusan must be blocked
    $this->actingAs($admin)->delete(route('admin.jurusan.destroy', $jurusan))
        ->assertRedirect(route('admin.jurusan.index'))
        ->assertSessionHas('error');

    // Unlinked Jurusan can be deleted cleanly
    $emptyJurusan = Jurusan::create(['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual', 'is_aktif' => true]);
    $this->actingAs($admin)->delete(route('admin.jurusan.destroy', $emptyJurusan))
        ->assertRedirect(route('admin.jurusan.index'))
        ->assertSessionHas('success');
    expect(Jurusan::find($emptyJurusan->id))->toBeNull();
});

test('erapor and cetak rapor scope subjects by jurusan and tingkat with grouped headings', function () {
    $admin = User::factory()->create(['role' => 'admin_sekolah', 'is_active' => true]);
    $ta = TahunAjaran::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_aktif' => true,
    ]);

    $akl = Jurusan::create(['kode' => 'AKL', 'singkatan' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'is_aktif' => true]);
    $bd = Jurusan::create(['kode' => 'BD', 'singkatan' => 'BD', 'nama' => 'Bisnis Digital', 'is_aktif' => true]);

    $rombelXAk = Rombel::create([
        'tahun_ajaran_id' => $ta->id,
        'semester' => 'Ganjil',
        'nama' => 'X AK',
        'tingkat' => 'X',
        'jurusan_id' => $akl->id,
        'kompetensi_keahlian' => $akl->nama,
        'is_aktif' => true,
    ]);
    $rombelXBd = Rombel::create([
        'tahun_ajaran_id' => $ta->id,
        'semester' => 'Ganjil',
        'nama' => 'X BD',
        'tingkat' => 'X',
        'jurusan_id' => $bd->id,
        'kompetensi_keahlian' => $bd->nama,
        'is_aktif' => true,
    ]);

    // Admin creates subjects via MataPelajaran CRUD
    $this->actingAs($admin)->post(route('admin.mata-pelajaran.store'), [
        'kode' => 'A.03',
        'nama' => 'Bahasa Indonesia',
        'kelompok' => 'umum',
        'jurusan_id' => null,
        'tingkat' => [],
        'urutan' => 10,
        'kkm' => 75,
    ])->assertRedirect(route('admin.mata-pelajaran.index'));

    $this->actingAs($admin)->post(route('admin.mata-pelajaran.store'), [
        'kode' => 'AKL-A9',
        'nama' => 'Akuntansi Dasar',
        'kelompok' => 'kejuruan',
        'jurusan_id' => $akl->id,
        'tingkat' => ['X'],
        'urutan' => 100,
        'kkm' => 75,
    ])->assertRedirect(route('admin.mata-pelajaran.index'));

    $this->actingAs($admin)->post(route('admin.mata-pelajaran.store'), [
        'kode' => 'AKL-B7',
        'nama' => 'Audit Dasar',
        'kelompok' => 'kejuruan',
        'jurusan_id' => $akl->id,
        'tingkat' => ['XII'],
        'urutan' => 110,
        'kkm' => 75,
    ])->assertRedirect(route('admin.mata-pelajaran.index'));

    $this->actingAs($admin)->post(route('admin.mata-pelajaran.store'), [
        'kode' => 'BD-B9',
        'nama' => 'Search Engine Optimization',
        'kelompok' => 'kejuruan',
        'jurusan_id' => $bd->id,
        'tingkat' => ['X', 'XI', 'XII'],
        'urutan' => 120,
        'kkm' => 75,
    ])->assertRedirect(route('admin.mata-pelajaran.index'));

    $siswaXAk = Siswa::create([
        'nisn' => '0101112223',
        'nama_lengkap' => 'Rina Akuntansi',
        'jenis_kelamin' => 'P',
        'kelas' => 'X AK',
        'rombel_id' => $rombelXAk->id,
        'tahun_ajaran_id' => $ta->id,
        'status_siswa' => 'aktif',
    ]);

    // Verify MataPelajaran::forClass scoping
    $xAkMapels = MataPelajaran::forClass('X AK', $ta->id)->pluck('nama')->all();
    expect($xAkMapels)->toBe(['Bahasa Indonesia', 'Akuntansi Dasar']);

    $xBdMapels = MataPelajaran::forClass('X BD', $ta->id)->pluck('nama')->all();
    expect($xBdMapels)->toBe(['Bahasa Indonesia', 'Search Engine Optimization']);

    // Buku Nilai only lists scoped subjects for X AK
    $this->actingAs($admin)->get('/erapor/buku-nilai?kelas=X AK&tahun_ajaran_id=' . $ta->id)
        ->assertStatus(200)
        ->assertSee('Bahasa Indonesia')
        ->assertSee('Akuntansi Dasar')
        ->assertDontSee('Search Engine Optimization')
        ->assertDontSee('Audit Dasar');

    // Rekap STS only shows X AK subjects
    $this->actingAs($admin)->get('/erapor/recap?kelas=X AK&tahun_ajaran_id=' . $ta->id)
        ->assertStatus(200)
        ->assertSee('Akuntansi Dasar')
        ->assertDontSee('Search Engine Optimization');

    // Cetak Rapor Print shows grouped sections (A. Umum, B. Kejuruan) and only X AK subjects
    $printRes = $this->actingAs($admin)->get(route('erapor.cetak.siswa', [
        'siswa' => $siswaXAk->id,
        'tahun_ajaran_id' => $ta->id,
        'semester' => 'Ganjil',
        'jenis_rapor' => 'uas',
    ]));
    $printRes->assertStatus(200)
        ->assertSee('A. KELOMPOK MATA PELAJARAN UMUM')
        ->assertSee('B. KELOMPOK MATA PELAJARAN KEJURUAN')
        ->assertSee('Bahasa Indonesia')
        ->assertSee('Akuntansi Dasar')
        ->assertDontSee('Search Engine Optimization')
        ->assertDontSee('Audit Dasar');
});

test('erapor rapor subject seeder configures exact Kelas X, XI, and XII lists per jurusan while hiding non-rapor schedule subjects', function () {
    $admin = User::factory()->create(['role' => 'admin_sekolah', 'is_active' => true]);
    $ta = TahunAjaran::create([
        'nama' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_aktif' => true,
    ]);

    $akl = Jurusan::create(['kode' => 'AKL', 'singkatan' => 'AK', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'is_aktif' => true]);
    $mplb = Jurusan::create(['kode' => 'MPLB', 'singkatan' => 'MP', 'nama' => 'Manajemen Perkantoran dan Layanan Bisnis', 'is_aktif' => true]);
    $bd = Jurusan::create(['kode' => 'BD', 'singkatan' => 'BD', 'nama' => 'Bisnis Digital', 'is_aktif' => true]);

    $rombelMap = [];
    foreach ([
        ['X AK', 'X', $akl],
        ['X MP 1', 'X', $mplb],
        ['X BD', 'X', $bd],
        ['XI AK 1', 'XI', $akl],
        ['XI MP 1', 'XI', $mplb],
        ['XI BD', 'XI', $bd],
        ['XII AK', 'XII', $akl],
        ['XII MP 1', 'XII', $mplb],
        ['XII BD', 'XII', $bd],
    ] as [$nama, $tingkat, $jur]) {
        $rombelMap[$nama] = Rombel::create([
            'tahun_ajaran_id' => $ta->id,
            'semester' => 'Ganjil',
            'nama' => $nama,
            'tingkat' => $tingkat,
            'jurusan_id' => $jur->id,
            'kompetensi_keahlian' => $jur->nama,
            'is_aktif' => true,
        ]);
    }

    // Legacy schedule-only subject linked to Jadwal must remain in DB but hidden from Rapor
    $legacyMapel = MataPelajaran::create([
        'kode' => 'AKL-A2',
        'nama' => 'Perbankan Dasar',
        'kelompok' => 'kejuruan',
        'jurusan_id' => $akl->id,
        'tingkat' => ['X'],
        'urutan' => 160,
    ]);
    $guruUser = User::factory()->create(['role' => 'guru', 'is_active' => true]);
    $guru = Guru::create(['user_id' => $guruUser->id, 'nip' => '19800101001', 'nama_lengkap' => 'Guru Jadwal', 'jenis_kelamin' => 'L']);
    $lokasi = \App\Models\Lokasi::create(['nama' => 'Kampus', 'latitude' => -6.2, 'longitude' => 106.8, 'radius' => 100]);
    \App\Models\Jadwal::create([
        'tahun_ajaran_id' => $ta->id,
        'kelas' => 'X AK',
        'mata_pelajaran_id' => $legacyMapel->id,
        'guru_id' => $guru->id,
        'lokasi_id' => $lokasi->id,
        'hari' => 'Senin',
        'jam_mulai' => '07:30',
        'jam_selesai' => '09:00',
    ]);

    $this->seed(\Database\Seeders\RaporMataPelajaranSeeder::class);

    // Jadwal stays intact and legacy subject is marked tampil_di_rapor = false
    expect(\App\Models\Jadwal::count())->toBe(1);
    expect($legacyMapel->fresh()->tampil_di_rapor)->toBeFalse();

    $xUmumAndKejuruanBase = [
        'Pendidikan Agama dan Budi Pekerti',
        'Pendidikan Pancasila',
        'Bahasa Indonesia',
        'Pendidikan Jasmani Olahraga dan Kesehatan',
        'Sejarah',
        'Seni',
        'Matematika (Umum)',
        'Bahasa Inggris',
        'Informatika',
        'Projek Ilmu Pengetahuan Alam dan Sosial',
    ];

    expect(MataPelajaran::forClass('X AK', $ta->id)->pluck('nama')->all())->toBe(array_merge(
        $xUmumAndKejuruanBase,
        ['Dasar-dasar Akuntansi dan Keuangan Lembaga']
    ));
    expect(MataPelajaran::forClass('X MP 1', $ta->id)->pluck('nama')->all())->toBe(array_merge(
        $xUmumAndKejuruanBase,
        ['Dasar-dasar Manajemen Perkantoran dan Layanan Bisnis']
    ));
    expect(MataPelajaran::forClass('X BD', $ta->id)->pluck('nama')->all())->toBe(array_merge(
        $xUmumAndKejuruanBase,
        ['Dasar-dasar Pemasaran']
    ));

    $xiBase = [
        'Pendidikan Agama dan Budi Pekerti',
        'Pendidikan Pancasila',
        'Bahasa Indonesia',
        'Pendidikan Jasmani Olahraga dan Kesehatan',
        'Sejarah',
        'Muatan Lokal BTQ',
        'Matematika (Umum)',
        'Bahasa Inggris',
    ];

    $expectedMp = array_merge($xiBase, [
        'Pengelolaan Administrasi Umum',
        'Komunikasi di Tempat Kerja',
        'Pengelolaan Kearsipan',
        'Teknologi Perkantoran',
        'Projek Kreatif dan Kewirausahaan',
        'Mata Pelajaran Pilihan (Teknik Perkantoran)',
    ]);
    $expectedAk = array_merge($xiBase, [
        'Praktikum Akuntansi Perusahaan Jasa, Dagang',
        'Praktikum Akuntansi Lembaga/Instansi Pemerintah',
        'Akuntansi Keuangan',
        'Komputer Akuntansi/Myob',
        'Administrasi Pajak',
        'Projek Kreatif dan Kewirausahaan',
    ]);
    $expectedBd = array_merge($xiBase, [
        'Ekonomi Bisnis dan Adm. Umum',
        'Marketing',
        'Perencanaan Bisnis',
        'Komunikasi dengan Pelanggan',
        'Projek Kreatif dan Kewirausahaan',
        'Mata Pelajaran Pilihan (Bisnis Digital)',
    ]);

    expect(MataPelajaran::forClass('XI MP 1', $ta->id)->pluck('nama')->all())->toBe($expectedMp);
    expect(MataPelajaran::forClass('XII MP 1', $ta->id)->pluck('nama')->all())->toBe($expectedMp);
    expect(MataPelajaran::forClass('XI AK 1', $ta->id)->pluck('nama')->all())->toBe($expectedAk);
    expect(MataPelajaran::forClass('XII AK', $ta->id)->pluck('nama')->all())->toBe($expectedAk);
    expect(MataPelajaran::forClass('XI BD', $ta->id)->pluck('nama')->all())->toBe($expectedBd);
    expect(MataPelajaran::forClass('XII BD', $ta->id)->pluck('nama')->all())->toBe($expectedBd);

    // Verify dynamic Konsentrasi Keahlian headings on UAS print
    foreach ([
        ['XI MP 1', '0110001', 'Siswa MP', 'C. KONSENTRASI KEAHLIAN MANAJEMEN PERKANTORAN'],
        ['XI AK 1', '0110002', 'Siswa AK', 'C. KONSENTRASI KEAHLIAN AKUNTANSI'],
        ['XI BD', '0110003', 'Siswa BD', 'C. KONSENTRASI KEAHLIAN BISNIS DIGITAL'],
    ] as [$kelas, $nisn, $namaSiswa, $expectedHeading]) {
        $siswa = Siswa::create([
            'nisn' => $nisn,
            'nama_lengkap' => $namaSiswa,
            'jenis_kelamin' => 'P',
            'kelas' => $kelas,
            'rombel_id' => $rombelMap[$kelas]->id,
            'tahun_ajaran_id' => $ta->id,
            'status_siswa' => 'aktif',
        ]);

        $this->actingAs($admin)->get(route('erapor.cetak.siswa', [
            'siswa' => $siswa->id,
            'tahun_ajaran_id' => $ta->id,
            'semester' => 'Ganjil',
            'jenis_rapor' => 'uas',
        ]))->assertStatus(200)
            ->assertSee('A. KELOMPOK MATA PELAJARAN UMUM')
            ->assertSee('B. KELOMPOK MATA PELAJARAN KEJURUAN')
            ->assertSee($expectedHeading);
    }
});
