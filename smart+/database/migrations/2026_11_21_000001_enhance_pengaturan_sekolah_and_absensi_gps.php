<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengaturan_sekolah MODIFY COLUMN metode_absensi_siswa VARCHAR(30) NOT NULL DEFAULT 'RFID'");
            DB::statement("ALTER TABLE pengaturan_sekolah MODIFY COLUMN metode_absensi_guru VARCHAR(30) NOT NULL DEFAULT 'RFID'");
            DB::statement("ALTER TABLE pengaturan_sekolah MODIFY COLUMN metode_absensi_staff VARCHAR(30) NOT NULL DEFAULT 'RFID'");
        } else {
            Schema::table('pengaturan_sekolah', function (Blueprint $table) {
                $table->string('metode_absensi_siswa', 30)->default('RFID')->change();
                $table->string('metode_absensi_guru', 30)->default('RFID')->change();
                $table->string('metode_absensi_staff', 30)->default('RFID')->change();
            });
        }

        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            if (!Schema::hasColumn('pengaturan_sekolah', 'mode_lokasi_gps')) {
                $table->string('mode_lokasi_gps', 30)->default('semua_lokasi')->after('metode_absensi_staff');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'validasi_radius_gps')) {
                $table->boolean('validasi_radius_gps')->default(true)->after('mode_lokasi_gps');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'wajib_foto_gps')) {
                $table->boolean('wajib_foto_gps')->default(true)->after('validasi_radius_gps');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'izinkan_absen_tanpa_jadwal')) {
                $table->boolean('izinkan_absen_tanpa_jadwal')->default(true)->after('wajib_foto_gps');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'absensi_hari_sabtu')) {
                $table->boolean('absensi_hari_sabtu')->default(false)->after('izinkan_absen_tanpa_jadwal');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'absensi_hari_minggu')) {
                $table->boolean('absensi_hari_minggu')->default(false)->after('absensi_hari_sabtu');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE absensi_siswa MODIFY COLUMN jadwal_id BIGINT UNSIGNED NULL");
        } else {
            Schema::table('absensi_siswa', function (Blueprint $table) {
                $table->unsignedBigInteger('jadwal_id')->nullable()->change();
            });
        }

        Schema::table('absensi_siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi_siswa', 'lokasi_id')) {
                $table->foreignId('lokasi_id')->nullable()->after('jadwal_id')->constrained('lokasi')->nullOnDelete();
            }
            if (!Schema::hasColumn('absensi_siswa', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('alasan_perubahan');
            }
            if (!Schema::hasColumn('absensi_siswa', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('absensi_siswa', 'foto_lokasi')) {
                $table->string('foto_lokasi')->nullable()->after('longitude');
            }
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi_guru', 'lokasi_id')) {
                $table->foreignId('lokasi_id')->nullable()->after('guru_id')->constrained('lokasi')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            if (Schema::hasColumn('absensi_guru', 'lokasi_id')) {
                $table->dropConstrainedForeignId('lokasi_id');
            }
        });

        Schema::table('absensi_siswa', function (Blueprint $table) {
            if (Schema::hasColumn('absensi_siswa', 'lokasi_id')) {
                $table->dropConstrainedForeignId('lokasi_id');
            }
            foreach (['latitude', 'longitude', 'foto_lokasi'] as $col) {
                if (Schema::hasColumn('absensi_siswa', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            foreach ([
                'mode_lokasi_gps',
                'validasi_radius_gps',
                'wajib_foto_gps',
                'izinkan_absen_tanpa_jadwal',
                'absensi_hari_sabtu',
                'absensi_hari_minggu',
            ] as $col) {
                if (Schema::hasColumn('pengaturan_sekolah', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
