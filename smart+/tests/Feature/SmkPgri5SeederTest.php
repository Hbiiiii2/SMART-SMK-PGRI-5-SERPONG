<?php

use App\Models\DapodikSyncLog;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaOrangTua;
use App\Models\SiswaPeriodik;
use App\Models\SiswaProfil;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\SmkPgri5MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('smk pgri 5 master seeder seeds school settings, 13 rombels, 443 siswa, 25 guru, 51 mapel, and full 2026/2027 schedule idempotently', function () {
    $this->seed(SmkPgri5MasterSeeder::class);

    $ta = TahunAjaran::where('is_aktif', true)->first();
    expect($ta)->not->toBeNull();
    expect($ta->nama)->toBe('2026/2027');

    $pengaturan = PengaturanSekolah::current();
    expect($pengaturan->nama_sekolah)->toBe('SMKS PGRI 5 Serpong');
    expect($pengaturan->jam_masuk)->toStartWith('12:30');
    expect($pengaturan->jam_pulang)->toStartWith('17:20');
    expect($pengaturan->absensi_hari_sabtu)->toBeTrue();

    expect(Rombel::where('tahun_ajaran_id', $ta->id)->count())->toBe(13);
    expect(Rombel::where('tahun_ajaran_id', $ta->id)->whereNotNull('jurusan_id')->count())->toBe(13);
    expect(\App\Models\Jurusan::count())->toBe(3);

    $xAkSubjects = MataPelajaran::forClass('X AK', $ta->id);
    $xAkCodes = $xAkSubjects->pluck('kode')->all();
    expect(count($xAkCodes))->toBeGreaterThan(8)->toBeLessThan(20);
    expect($xAkCodes)->toContain('AKL-A1')->not->toContain('BD-B9')->not->toContain('AKL-B7');
    expect(Siswa::count())->toBe(443);
    expect(SiswaProfil::count())->toBe(443);
    expect(SiswaPeriodik::count())->toBe(442);
    expect(SiswaOrangTua::count())->toBeGreaterThan(800);
    expect(User::where('role', 'siswa')->count())->toBe(443);

    expect(Guru::count())->toBe(25);
    expect(User::where('role', 'guru')->count())->toBe(25);
    expect(User::where('email', 'like', '%@siswa.%')->count())->toBe(0);
    expect(User::where('email', 'like', '%@guru.%')->count())->toBe(0);

    $firstStudent = Siswa::with('user')->where('nipd', '2627.001')->first();
    expect($firstStudent?->user?->email)->toBe('2627.001@smkpgri5serpong.sch.id');
    expect(User::where('email', 'encum@smkpgri5serpong.sch.id')->where('role', 'guru')->exists())->toBeTrue();
    expect(User::where('email', 'wawan@smkpgri5serpong.sch.id')->where('role', 'guru')->exists())->toBeTrue();

    $scheduleData = json_decode(
        file_get_contents(database_path('seeders/data/jadwal_smk_pgri_5_2026_2027.json')),
        true
    );

    expect(MataPelajaran::count())->toBeGreaterThanOrEqual(count($scheduleData['subjects']));
    expect(Jadwal::where('tahun_ajaran_id', $ta->id)->count())->toBe(count($scheduleData['schedule_blocks']));

    $rombelNames = Rombel::where('tahun_ajaran_id', $ta->id)->pluck('nama')->all();
    $jadwalClasses = Jadwal::where('tahun_ajaran_id', $ta->id)->distinct()->pluck('kelas')->all();
    expect(array_diff($jadwalClasses, $rombelNames))->toBeEmpty();
    expect(array_diff($rombelNames, $jadwalClasses))->toBeEmpty();

    $lastLog = DapodikSyncLog::latest('id')->first();
    expect($lastLog)->not->toBeNull();
    expect($lastLog->error_count)->toBe(0);
    expect($lastLog->inserted_count)->toBe(443);

    // Run a second time to verify strict idempotency (no duplicates)
    $this->seed(SmkPgri5MasterSeeder::class);

    expect(Rombel::where('tahun_ajaran_id', $ta->id)->count())->toBe(13);
    expect(Rombel::where('tahun_ajaran_id', $ta->id)->whereNotNull('jurusan_id')->count())->toBe(13);
    expect(\App\Models\Jurusan::count())->toBe(3);

    $xAkSubjects = MataPelajaran::forClass('X AK', $ta->id);
    $xAkCodes = $xAkSubjects->pluck('kode')->all();
    expect(count($xAkCodes))->toBeGreaterThan(8)->toBeLessThan(20);
    expect($xAkCodes)->toContain('AKL-A1')->not->toContain('BD-B9')->not->toContain('AKL-B7');
    expect(Siswa::count())->toBe(443);
    expect(SiswaProfil::count())->toBe(443);
    expect(User::where('role', 'siswa')->count())->toBe(443);
    expect(Guru::count())->toBe(25);
    expect(User::where('role', 'guru')->count())->toBe(25);
    expect(User::where('email', 'like', '%@siswa.%')->count())->toBe(0);
    expect(User::where('email', 'like', '%@guru.%')->count())->toBe(0);

    $firstStudent = Siswa::with('user')->where('nipd', '2627.001')->first();
    expect($firstStudent?->user?->email)->toBe('2627.001@smkpgri5serpong.sch.id');
    expect(User::where('email', 'encum@smkpgri5serpong.sch.id')->where('role', 'guru')->exists())->toBeTrue();
    expect(User::where('email', 'wawan@smkpgri5serpong.sch.id')->where('role', 'guru')->exists())->toBeTrue();
    expect(Jadwal::where('tahun_ajaran_id', $ta->id)->count())->toBe(count($scheduleData['schedule_blocks']));
});
