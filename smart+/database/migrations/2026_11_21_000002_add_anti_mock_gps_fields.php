<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            if (!Schema::hasColumn('pengaturan_sekolah', 'mode_anti_mock_gps')) {
                $table->string('mode_anti_mock_gps', 20)->default('peringatan');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'maks_akurasi_gps_meter')) {
                $table->unsignedInteger('maks_akurasi_gps_meter')->default(100);
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'mode_deteksi_perangkat_bersama')) {
                $table->string('mode_deteksi_perangkat_bersama', 20)->default('tandai');
            }
            if (!Schema::hasColumn('pengaturan_sekolah', 'batas_akun_per_perangkat')) {
                $table->unsignedSmallInteger('batas_akun_per_perangkat')->default(2);
            }
        });

        foreach (['absensi_siswa', 'absensi_guru', 'absensi_staff'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'gps_accuracy')) {
                    $table->decimal('gps_accuracy', 8, 2)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'device_token')) {
                    $table->string('device_token', 64)->nullable();
                    $table->index('device_token', "idx_{$tableName}_device_token");
                }
                if (!Schema::hasColumn($tableName, 'is_mock_suspect')) {
                    $table->boolean('is_mock_suspect')->default(false);
                }
                if (!Schema::hasColumn($tableName, 'mock_gps_reason')) {
                    $table->string('mock_gps_reason', 500)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['absensi_siswa', 'absensi_guru', 'absensi_staff'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'device_token')) {
                    try {
                        $table->dropIndex("idx_{$tableName}_device_token");
                    } catch (\Throwable $e) {
                        // Ignore if index already dropped or differently named
                    }
                }
                foreach (['gps_accuracy', 'device_token', 'is_mock_suspect', 'mock_gps_reason'] as $col) {
                    if (Schema::hasColumn($tableName, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            foreach ([
                'mode_anti_mock_gps',
                'maks_akurasi_gps_meter',
                'mode_deteksi_perangkat_bersama',
                'batas_akun_per_perangkat',
            ] as $col) {
                if (Schema::hasColumn('pengaturan_sekolah', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
