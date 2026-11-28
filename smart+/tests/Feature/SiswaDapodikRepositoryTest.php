<?php

namespace Tests\Feature;

use App\Models\DapodikSyncLog;
use App\Models\PengaturanSekolah;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\DapodikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiswaDapodikRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TahunAjaran $tahunAjaran;
    private Rombel $rombelXAk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $this->tahunAjaran = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_aktif' => true,
        ]);

        $this->rombelXAk = Rombel::create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'nama' => 'X AK',
            'tingkat' => 'X',
            'kompetensi_keahlian' => 'Akuntansi dan Keuangan Lembaga',
            'is_aktif' => true,
        ]);
    }

    public function test_admin_can_create_student_with_optional_nisn_nipd_and_without_login_account(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.siswa.store'), [
            'nama_lengkap' => 'Siswa Baru Tanpa NISN',
            'rombel_id' => $this->rombelXAk->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nisn' => null,
            'nipd' => null,
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Tangerang Selatan',
            'tanggal_lahir' => '15-08-2010',
            'agama' => 'Islam',
            'email_pribadi' => 'ortu.shared@gmail.com',
            'no_wa_aktif' => '081234567899',
            'profil' => [
                'kelurahan' => 'Serpong',
                'kecamatan' => 'Kec. Serpong',
                'penerima_kip' => '1',
                'nomor_kip' => 'KIP12345',
            ],
            'ortu' => [
                'ibu' => [
                    'nama' => 'Siti Aminah',
                    'pekerjaan' => 'Tidak bekerja',
                    'penghasilan' => 'Tidak Berpenghasilan',
                ],
            ],
            'periodik' => [
                'berat_badan' => 48,
                'tinggi_badan' => 158,
            ],
        ]);

        $response->assertRedirect(route('admin.siswa.index'));

        $siswa = Siswa::with(['profil', 'orangTuas', 'latestPeriodik'])->where('nama_lengkap', 'Siswa Baru Tanpa NISN')->firstOrFail();
        $this->assertNull($siswa->user_id);
        $this->assertNull($siswa->nisn);
        $this->assertNull($siswa->nipd);
        $this->assertSame('X AK', $siswa->kelas);
        $this->assertSame($this->rombelXAk->id, $siswa->rombel_id);
        $this->assertSame('ortu.shared@gmail.com', $siswa->email_pribadi);
        $this->assertTrue((bool) $siswa->profil?->penerima_kip);
        $this->assertSame('Siti Aminah', $siswa->orangTuas->firstWhere('hubungan', 'ibu')?->nama);
        $this->assertEquals(48.0, $siswa->latestPeriodik?->berat_badan);

        $this->actingAs($this->admin)->get(route('admin.siswa.show', $siswa))
            ->assertStatus(200)
            ->assertSee('Siswa Baru Tanpa NISN')
            ->assertSee('Siti Aminah');
    }

    public function test_dapodik_csv_import_handles_real_smk_pgri_anomalies_and_idempotent_upsert(): void
    {
        $csv = implode("\n", [
            'Daftar Peserta Didik,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'SMKS PGRI 5 SERPONG,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            '"Kecamatan Kec. Serpong, Kabupaten Kota Tangerang Selatan, Provinsi Prov. Banten",,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'Tanggal Unduh: 2026-08-23 12:24:12,,,Pengunduh: ARIEF R. HAKIM (pgri5serpong@gmail.com),,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'No,Rombel Saat Ini,Nama,NIPD,JK,NISN,Tempat Lahir,Tanggal Lahir,NIK,Agama,Alamat,RT,RW,Dusun,Kelurahan,Kecamatan,Kode Pos,Jenis Tinggal,Alat Transportasi,Telepon,HP,E-Mail,SKHUN,Penerima KPS,No. KPS,Data Ayah,,,,,,Data Ibu,,,,,,Data Wali,,,,,,Rombel Saat Ini,No Peserta Ujian Nasional,No Seri Ijazah,Penerima KIP,Nomor KIP,Nama di KIP,Nomor KKS,No Registrasi Akta Lahir,Bank,Nomor Rekening Bank,Rekening Atas Nama,Layak PIP (usulan dari sekolah),Alasan Layak PIP,Kebutuhan Khusus,Sekolah Asal,Anak ke-berapa,Lintang,Bujur,No KK,Berat Badan,Tinggi Badan,Lingkar Kepala,"Jml. Saudara',
            'Kandung","Jarak Rumah',
            'ke Sekolah (KM)"',
            ',,,,,,,,,,,,,,,,,,,,,,,,,Nama,Tahun Lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan,NIK,Nama,Tahun Lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan,NIK,Nama,Tahun Lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan,NIK,,,,,,,,,,,,,,,,,,,,,,,,',
            '1,X AK,ABDUL RAHMAN BAIHAKI,2627.001,L,0113765921,BOGOR,2011-06-25,3201112506110005,Islam,KP. JELETRENG,2,4,,PENGASINAN,Kec. Gunung Sindur,16340,Bersama orang tua,Jalan kaki,,081200001111,smkpgri05serpong@gmail.com,,Tidak,,SUMANTA,1970,SMA / sederajat,Wiraswasta,Rp1.000.001 â€“ Rp3.000.000,,GUSNILEFNITA,1982,SD / sederajat,Tidak bekerja,Tidak Berpenghasilan,,,,Tidak sekolah,,< Rp1.000.000,,X AK,,,Tidak,,0,,,,,,Tidak,,Tidak ada,SMP NEGERI 2 GUNUNGSINDUR,0,-6.361,106.6832,,53,155,0,4,7',
            '2,X AK,ABI MANYU,2627.002,L,3105128346,TANGERANG,2010-05-07,3603230705100004,Islam,"Rancamas No. 30",2,4,CIBOGO,CIBOGO,,15343,Bersama orang tua,Sepeda motor,089507370351,089507370351,smkpgri05serpong@gmail.com,,Tidak,,TOTOK SUGIARTO,1974,SMA / sederajat,Karyawan Swasta,"Rp. 1,000,000 - Rp. 1,999,999",3603231501580002,SUTINI,1977,SMP / sederajat,Wirausaha,"Rp. 5,000,000 - Rp. 20,000,000",3603234711740001,TOTOK SUGIARTO,,Putus SD,Karyawan Swasta,,3603231501580002,X AK,,,Tidak,-,1,,3603-LT-21062023-0096,,,,Tidak,,Tidak ada,,1,,,3320081007060005,54,160,0,2,4',
            '94,X MP 1,MUHAMMAD ADITYA PRANDANA,2627.095,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            '376,XII MP 1,Alya friskandardini,2425.043,P,0097300265,Tangerang,2009-05-24,NULL            ,Islam,Griya Suradita Indah,1,1,,Suradita,Kec. Cisauk,,Bersama orang tua,Angkutan umum/bus/pete-pete,,,,,Tidak,,,0,,,,,Murni Fransiska,0,SMP / sederajat,Tidak bekerja,Tidak Berpenghasilan,,,,,,,,XII MP 1,,,Tidak,,0,,,,,,Tidak,,Tidak ada,SMP NEGERI 3 CISAUK,1,-6.3562,106.6332,,45,167,0,2,11',
            ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            ',,: sudah keluar/mutasi,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
        ]);

        // Fix the multiline column header so rows stay aligned (join the 2 multiline header breaks with \n inside quotes)
        $csv = str_replace(
            "\"Jml. Saudara\nKandung\",\"Jarak Rumah\nke Sekolah (KM)\"",
            "\"Jml. Saudara\nKandung\",\"Jarak Rumah\nke Sekolah (KM)\"",
            $csv
        );

        $file = UploadedFile::fake()->createWithContent('daftar_peserta_didik.csv', $csv);

        // 1. Preview first
        $this->actingAs($this->admin)->post(route('admin.siswa.dapodik.preview'), [
            'file' => $file,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
        ])->assertRedirect(route('admin.siswa.dapodik.index'))
            ->assertSessionHas('dapodik_preview');

        // 2. Actual Import (create login accounts enabled, shared parent email must NOT fail unique users.email!)
        $file2 = UploadedFile::fake()->createWithContent('daftar_peserta_didik.csv', $csv);
        $this->actingAs($this->admin)->post(route('admin.siswa.dapodik.import'), [
            'file' => $file2,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'create_accounts' => '1',
        ])->assertRedirect(route('admin.siswa.dapodik.index'))
            ->assertSessionHas('success');

        $this->assertSame(4, Siswa::count());

        $abdul = Siswa::with(['profil', 'orangTuas', 'latestPeriodik', 'user'])->where('nipd', '2627.001')->firstOrFail();
        $abi = Siswa::with('user')->where('nipd', '2627.002')->firstOrFail();
        $aditya = Siswa::where('nipd', '2627.095')->firstOrFail();
        $alya = Siswa::where('nipd', '2425.043')->firstOrFail();

        // Shared email stored on email_pribadi, while login users.email are unique
        $this->assertSame('smkpgri05serpong@gmail.com', $abdul->email_pribadi);
        $this->assertSame('smkpgri05serpong@gmail.com', $abi->email_pribadi);
        $this->assertNotSame($abdul->user?->email, $abi->user?->email);

        // Mojibake & penghasilan normalized
        $this->assertSame('Rp 1.000.000 - Rp 3.000.000', $abdul->orangTuas->firstWhere('hubungan', 'ayah')?->penghasilan);
        $this->assertNull($abdul->profil?->nama_di_kip); // "0" stripped

        // Row with empty NISN/JK imported cleanly
        $this->assertNull($aditya->nisn);
        $this->assertSame('X MP 1', $aditya->kelas);

        // Literal "NULL            " cleaned to null
        $this->assertNull($alya->nik);

        // Auto-created master rombels
        $this->assertDatabaseHas('rombels', ['nama' => 'X MP 1', 'tingkat' => 'X']);
        $this->assertDatabaseHas('rombels', ['nama' => 'XII MP 1', 'tingkat' => 'XII']);

        // Set local-only WhatsApp number on Abdul and verify re-import does NOT overwrite it or duplicate rows
        $abdul->update(['no_wa_aktif' => '081299998888']);

        $file3 = UploadedFile::fake()->createWithContent('daftar_peserta_didik.csv', $csv);
        $this->actingAs($this->admin)->post(route('admin.siswa.dapodik.import'), [
            'file' => $file3,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'create_accounts' => '1',
        ])->assertRedirect(route('admin.siswa.dapodik.index'));

        $this->assertSame(4, Siswa::count());
        $this->assertSame('081299998888', $abdul->fresh()->no_wa_aktif);
        $this->assertSame(2, DapodikSyncLog::count());

        // Export Excel returns 200
        $this->actingAs($this->admin)->get(route('admin.siswa.dapodik.export'))
            ->assertStatus(200);
    }

    public function test_dapodik_webservice_api_sync_and_smart_api_v1_contract(): void
    {
        PengaturanSekolah::current()->update([
            'npsn' => '20603318',
            'dapodik_url' => 'http://192.168.1.10:5774',
            'dapodik_token' => 'secret-dapodik-token',
            'dapodik_semester_id' => '20261',
        ]);

        Http::fake([
            'http://192.168.1.10:5774/WebService/getSekolah*' => Http::response([
                'rows' => [['nama' => 'SMKS PGRI 5 SERPONG', 'npsn' => '20603318']],
            ], 200),
            'http://192.168.1.10:5774/WebService/getRombonganBelajar*' => Http::response([
                'rows' => [
                    [
                        'rombongan_belajar_id' => '11111111-1111-1111-1111-111111111111',
                        'nama' => 'XI BD',
                        'tingkat_pendidikan_id_str' => '11',
                        'jurusan_id_str' => 'Bisnis Digital',
                        'kurikulum_id_str' => 'SMK Merdeka',
                    ],
                ],
            ], 200),
            'http://192.168.1.10:5774/WebService/getPesertaDidik*' => Http::response([
                'rows' => [
                    [
                        'peserta_didik_id' => '22222222-2222-2222-2222-222222222222',
                        'registrasi_id' => '33333333-3333-3333-3333-333333333333',
                        'rombongan_belajar_id' => '11111111-1111-1111-1111-111111111111',
                        'anggota_rombel_id' => '44444444-4444-4444-4444-444444444444',
                        'nama' => 'Siti Aisyah API',
                        'nipd' => '2526.088',
                        'nisn' => '0109998887',
                        'nik' => '3674010101100009',
                        'jenis_kelamin' => 'P',
                        'tempat_lahir' => 'Tangerang',
                        'tanggal_lahir' => '2010-01-01',
                        'agama_id_str' => 'Islam',
                        'nama_rombel' => 'XI BD',
                        'nama_ibu' => 'Ibu Aisyah',
                    ],
                ],
            ], 200),
        ]);

        /** @var DapodikApiService $apiService */
        $apiService = app(DapodikApiService::class);
        $conn = $apiService->testConnection();
        $this->assertTrue($conn['success']);

        $log = $apiService->syncFromApi($this->tahunAjaran->id, 'Ganjil', false, $this->admin->id);
        $this->assertSame('success', $log->status);
        $this->assertSame(1, $log->inserted_count);

        $this->assertDatabaseHas('rombels', [
            'nama' => 'XI BD',
            'dapodik_rombongan_belajar_id' => '11111111-1111-1111-1111-111111111111',
        ]);
        $this->assertDatabaseHas('siswa', [
            'dapodik_peserta_didik_id' => '22222222-2222-2222-2222-222222222222',
            'nipd' => '2526.088',
            'nisn' => '0109998887',
            'kelas' => 'XI BD',
        ]);

        // Verify SmartApi v1 exposes nipd & SMK tingkat without leaking NIK
        $apiRes = $this->withHeader('Authorization', 'Bearer smart_secret_token_almadinah_2026')
            ->getJson('/api/v1/students');

        $apiRes->assertStatus(200)
            ->assertJsonFragment([
                'nipd' => '2526.088',
                'nis' => '2526.088',
                'nisn' => '0109998887',
                'kelas' => 'XI BD',
            ])
            ->assertJsonMissing(['nik' => '3674010101100009']);
    }
}
