<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Rombel / Kelas
        if (!Schema::hasTable('rombels')) {
            Schema::create('rombels', function (Blueprint $table) {
                $table->id();
                $table->char('dapodik_rombongan_belajar_id', 36)->nullable()->unique();
                $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
                $table->string('semester', 20)->default('Ganjil');
                $table->string('nama', 50);
                $table->string('tingkat', 15)->nullable();
                $table->string('kompetensi_keahlian', 120)->nullable();
                $table->string('kurikulum', 120)->nullable();
                $table->foreignId('wali_kelas_guru_id')->nullable()->constrained('guru')->nullOnDelete();
                $table->unsignedSmallInteger('kapasitas')->nullable();
                $table->boolean('is_aktif')->default(true);
                $table->timestamps();

                $table->unique(['tahun_ajaran_id', 'semester', 'nama'], 'unique_rombels_ta_sem_nama');
                $table->index(['nama', 'is_aktif'], 'idx_rombels_nama_aktif');
            });
        }

        // 2. Alter `siswa` for optional NISN/NIPD/user_id + Dapodik core fields
        if (DB::getDriverName() === 'mysql') {
            $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND CONSTRAINT_NAME = 'siswa_user_id_foreign'");
            if (!empty($fkExists)) {
                Schema::table('siswa', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }

            DB::statement('ALTER TABLE siswa MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE siswa MODIFY COLUMN nisn VARCHAR(20) NULL');
            DB::statement('ALTER TABLE siswa MODIFY COLUMN jenis_kelamin VARCHAR(10) NULL');
            DB::statement('ALTER TABLE siswa MODIFY COLUMN kelas VARCHAR(50) NULL');

            Schema::table('siswa', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            Schema::table('siswa', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->string('nisn', 20)->nullable()->change();
                $table->string('jenis_kelamin', 10)->nullable()->change();
                $table->string('kelas', 50)->nullable()->change();
            });
        }

        Schema::table('siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('siswa', 'rombel_id')) {
                $table->foreignId('rombel_id')->nullable()->after('kelas')->constrained('rombels')->nullOnDelete();
            }
            if (!Schema::hasColumn('siswa', 'dapodik_peserta_didik_id')) {
                $table->char('dapodik_peserta_didik_id', 36)->nullable()->unique()->after('user_id');
            }
            if (!Schema::hasColumn('siswa', 'dapodik_registrasi_id')) {
                $table->char('dapodik_registrasi_id', 36)->nullable()->index()->after('dapodik_peserta_didik_id');
            }
            if (!Schema::hasColumn('siswa', 'nipd')) {
                $table->string('nipd', 30)->nullable()->index()->after('dapodik_registrasi_id');
            }
            if (!Schema::hasColumn('siswa', 'nik')) {
                $table->string('nik', 20)->nullable()->index()->after('nisn');
            }
            if (!Schema::hasColumn('siswa', 'no_kk')) {
                $table->string('no_kk', 20)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('siswa', 'tempat_lahir')) {
                $table->string('tempat_lahir', 100)->nullable()->after('jenis_kelamin');
            }
            if (!Schema::hasColumn('siswa', 'agama')) {
                $table->string('agama', 30)->nullable()->after('tanggal_lahir');
            }
            if (!Schema::hasColumn('siswa', 'agama_id')) {
                $table->unsignedTinyInteger('agama_id')->nullable()->after('agama');
            }
            if (!Schema::hasColumn('siswa', 'telepon_rumah')) {
                $table->string('telepon_rumah', 25)->nullable()->after('no_telepon');
            }
            if (!Schema::hasColumn('siswa', 'no_wa_aktif')) {
                $table->string('no_wa_aktif', 25)->nullable()->after('telepon_rumah');
            }
            if (!Schema::hasColumn('siswa', 'email_pribadi')) {
                $table->string('email_pribadi', 150)->nullable()->after('no_wa_aktif');
            }
            if (!Schema::hasColumn('siswa', 'status_siswa')) {
                $table->string('status_siswa', 30)->default('aktif')->index()->after('tahun_ajaran_id');
            }
            if (!Schema::hasColumn('siswa', 'tanggal_masuk')) {
                $table->date('tanggal_masuk')->nullable()->after('status_siswa');
            }
            if (!Schema::hasColumn('siswa', 'tanggal_keluar')) {
                $table->date('tanggal_keluar')->nullable()->after('tanggal_masuk');
            }
            if (!Schema::hasColumn('siswa', 'alasan_keluar')) {
                $table->string('alasan_keluar', 255)->nullable()->after('tanggal_keluar');
            }
            if (!Schema::hasColumn('siswa', 'sync_source')) {
                $table->string('sync_source', 30)->default('manual')->after('alasan_keluar');
            }
            if (!Schema::hasColumn('siswa', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('sync_source');
            }
        });

        // 3. Siswa Profil (1-to-1: Domisili, Dokumen, Bantuan Sosial & Bank)
        if (!Schema::hasTable('siswa_profil')) {
            Schema::create('siswa_profil', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->unique()->constrained('siswa')->cascadeOnDelete();

                // Domisili
                $table->text('alamat_jalan')->nullable();
                $table->string('rt', 10)->nullable();
                $table->string('rw', 10)->nullable();
                $table->string('nama_dusun', 100)->nullable();
                $table->string('kelurahan', 100)->nullable();
                $table->string('kecamatan', 100)->nullable();
                $table->string('kode_pos', 15)->nullable();
                $table->string('kode_wilayah_dapodik', 20)->nullable();
                $table->string('jenis_tinggal', 100)->nullable();
                $table->string('alat_transportasi', 100)->nullable();
                $table->decimal('lintang', 11, 7)->nullable();
                $table->decimal('bujur', 11, 7)->nullable();

                // Dokumen & Akademik Awal
                $table->string('no_registrasi_akta_lahir', 100)->nullable();
                $table->string('skhun', 100)->nullable();
                $table->string('no_peserta_un', 100)->nullable();
                $table->string('no_seri_ijazah', 100)->nullable();
                $table->string('sekolah_asal', 150)->nullable();
                $table->string('kebutuhan_khusus', 100)->nullable();
                $table->unsignedTinyInteger('anak_keberapa')->nullable();

                // Kesejahteraan & Bank
                $table->boolean('penerima_kps')->default(false);
                $table->string('no_kps', 50)->nullable();
                $table->boolean('penerima_kip')->default(false);
                $table->string('nomor_kip', 50)->nullable();
                $table->string('nama_di_kip', 150)->nullable();
                $table->string('nomor_kks', 50)->nullable();
                $table->boolean('layak_pip')->default(false);
                $table->string('alasan_layak_pip', 150)->nullable();
                $table->string('bank', 100)->nullable();
                $table->string('nomor_rekening_bank', 50)->nullable();
                $table->string('rekening_atas_nama', 150)->nullable();

                $table->json('raw_dapodik_payload')->nullable();
                $table->timestamps();
            });
        }

        // 4. Siswa Orang Tua & Wali (1-to-Many)
        if (!Schema::hasTable('siswa_orang_tua')) {
            Schema::create('siswa_orang_tua', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->string('hubungan', 15); // ayah, ibu, wali
                $table->string('nama', 150)->nullable();
                $table->string('nik', 20)->nullable();
                $table->smallInteger('tahun_lahir')->nullable();
                $table->string('jenjang_pendidikan', 50)->nullable();
                $table->string('pekerjaan', 100)->nullable();
                $table->string('penghasilan', 100)->nullable();
                $table->string('no_hp', 25)->nullable();
                $table->string('status_hidup', 20)->default('hidup'); // hidup, meninggal
                $table->timestamps();

                $table->unique(['siswa_id', 'hubungan'], 'unique_siswa_ortu_hubungan');
            });
        }

        // 5. Siswa Periodik (1-to-Many per Tahun Ajaran & Semester)
        if (!Schema::hasTable('siswa_periodik')) {
            Schema::create('siswa_periodik', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
                $table->string('semester', 20)->default('Ganjil');
                $table->decimal('berat_badan', 5, 2)->nullable();
                $table->decimal('tinggi_badan', 5, 2)->nullable();
                $table->decimal('lingkar_kepala', 5, 2)->nullable();
                $table->unsignedTinyInteger('jumlah_saudara_kandung')->nullable();
                $table->decimal('jarak_rumah_km', 6, 2)->nullable();
                $table->timestamps();

                $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester'], 'unique_siswa_periodik_ta_sem');
            });
        }

        // 6. Extend `riwayat_kelas_siswa`
        if (Schema::hasTable('riwayat_kelas_siswa')) {
            Schema::table('riwayat_kelas_siswa', function (Blueprint $table) {
                if (!Schema::hasColumn('riwayat_kelas_siswa', 'rombel_id')) {
                    $table->foreignId('rombel_id')->nullable()->after('semester')->constrained('rombels')->nullOnDelete();
                }
                if (!Schema::hasColumn('riwayat_kelas_siswa', 'dapodik_anggota_rombel_id')) {
                    $table->char('dapodik_anggota_rombel_id', 36)->nullable()->after('rombel_id');
                }
            });
        }

        // 7. Dapodik Sync Logs
        if (!Schema::hasTable('dapodik_sync_logs')) {
            Schema::create('dapodik_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('source', 30); // excel, csv, api
                $table->string('entity', 50)->default('peserta_didik');
                $table->string('filename', 255)->nullable();
                $table->string('status', 20)->default('success'); // success, partial, failed
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('inserted_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('warning_count')->default(0);
                $table->unsignedInteger('error_count')->default(0);
                $table->text('summary_notes')->nullable();
                $table->json('anomalies')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        // 8. Extend `pengaturan_sekolah` with Dapodik WebService config
        if (Schema::hasTable('pengaturan_sekolah')) {
            Schema::table('pengaturan_sekolah', function (Blueprint $table) {
                if (!Schema::hasColumn('pengaturan_sekolah', 'npsn')) {
                    $table->string('npsn', 20)->nullable()->after('nama_sekolah');
                }
                if (!Schema::hasColumn('pengaturan_sekolah', 'dapodik_url')) {
                    $table->string('dapodik_url', 255)->nullable()->after('batas_akun_per_perangkat');
                }
                if (!Schema::hasColumn('pengaturan_sekolah', 'dapodik_token')) {
                    $table->text('dapodik_token')->nullable()->after('dapodik_url');
                }
                if (!Schema::hasColumn('pengaturan_sekolah', 'dapodik_semester_id')) {
                    $table->string('dapodik_semester_id', 10)->nullable()->after('dapodik_token');
                }
                if (!Schema::hasColumn('pengaturan_sekolah', 'dapodik_auto_sync')) {
                    $table->boolean('dapodik_auto_sync')->default(false)->after('dapodik_semester_id');
                }
                if (!Schema::hasColumn('pengaturan_sekolah', 'dapodik_last_sync_at')) {
                    $table->timestamp('dapodik_last_sync_at')->nullable()->after('dapodik_auto_sync');
                }
            });
        }

        // 9. Safe backfill: link existing siswa.kelas to master rombels
        if (Schema::hasTable('siswa') && Schema::hasTable('rombels')) {
            $activeYearId = DB::table('tahun_ajaran')->where('is_aktif', true)->value('id')
                ?? DB::table('tahun_ajaran')->orderByDesc('id')->value('id');

            $existingStudents = DB::table('siswa')
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->get(['id', 'kelas', 'tahun_ajaran_id']);

            foreach ($existingStudents as $row) {
                $kelas = trim((string) $row->kelas);
                if ($kelas === '') {
                    continue;
                }

                $lower = strtolower($kelas);
                if (str_starts_with($lower, 'alumni') || str_starts_with($lower, 'lulus')) {
                    DB::table('siswa')->where('id', $row->id)->update(['status_siswa' => 'lulus']);
                    continue;
                }

                $taId = $row->tahun_ajaran_id ?: $activeYearId;
                if (!$taId) {
                    continue;
                }

                $rombelId = DB::table('rombels')
                    ->where('tahun_ajaran_id', $taId)
                    ->where('semester', 'Ganjil')
                    ->where('nama', $kelas)
                    ->value('id');

                if (!$rombelId) {
                    preg_match('/^(XII|XI|X|IX|VIII|VII|\d+)/i', $kelas, $m);
                    $tingkat = isset($m[1]) ? strtoupper($m[1]) : null;
                    $rombelId = DB::table('rombels')->insertGetId([
                        'tahun_ajaran_id' => $taId,
                        'semester' => 'Ganjil',
                        'nama' => $kelas,
                        'tingkat' => $tingkat,
                        'is_aktif' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('siswa')->where('id', $row->id)->update(['rombel_id' => $rombelId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaturan_sekolah')) {
            Schema::table('pengaturan_sekolah', function (Blueprint $table) {
                $cols = array_filter([
                    'npsn',
                    'dapodik_url',
                    'dapodik_token',
                    'dapodik_semester_id',
                    'dapodik_auto_sync',
                    'dapodik_last_sync_at',
                ], fn ($col) => Schema::hasColumn('pengaturan_sekolah', $col));

                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('dapodik_sync_logs');

        if (Schema::hasTable('riwayat_kelas_siswa')) {
            Schema::table('riwayat_kelas_siswa', function (Blueprint $table) {
                if (Schema::hasColumn('riwayat_kelas_siswa', 'rombel_id')) {
                    $table->dropConstrainedForeignId('rombel_id');
                }
                if (Schema::hasColumn('riwayat_kelas_siswa', 'dapodik_anggota_rombel_id')) {
                    $table->dropColumn('dapodik_anggota_rombel_id');
                }
            });
        }

        Schema::dropIfExists('siswa_periodik');
        Schema::dropIfExists('siswa_orang_tua');
        Schema::dropIfExists('siswa_profil');

        if (Schema::hasTable('siswa')) {
            Schema::table('siswa', function (Blueprint $table) {
                if (Schema::hasColumn('siswa', 'rombel_id')) {
                    $table->dropConstrainedForeignId('rombel_id');
                }

                $dropCols = array_filter([
                    'dapodik_peserta_didik_id',
                    'dapodik_registrasi_id',
                    'nipd',
                    'nik',
                    'no_kk',
                    'tempat_lahir',
                    'agama',
                    'agama_id',
                    'telepon_rumah',
                    'no_wa_aktif',
                    'email_pribadi',
                    'status_siswa',
                    'tanggal_masuk',
                    'tanggal_keluar',
                    'alasan_keluar',
                    'sync_source',
                    'last_synced_at',
                ], fn ($col) => Schema::hasColumn('siswa', $col));

                if (!empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        Schema::dropIfExists('rombels');
    }
};
