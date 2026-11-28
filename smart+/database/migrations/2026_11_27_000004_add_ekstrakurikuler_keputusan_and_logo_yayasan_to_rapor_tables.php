<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_sts_summaries')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('student_sts_summaries', 'ekstrakurikuler')) {
                    $table->json('ekstrakurikuler')->nullable()->after('catatan_wali_kelas');
                }
                if (!Schema::hasColumn('student_sts_summaries', 'keputusan_status')) {
                    $table->string('keputusan_status', 30)->nullable()->after('ekstrakurikuler');
                }
                if (!Schema::hasColumn('student_sts_summaries', 'keputusan_kelas')) {
                    $table->string('keputusan_kelas', 60)->nullable()->after('keputusan_status');
                }
            });
        }

        if (Schema::hasTable('rapor_settings')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('rapor_settings', 'logo_yayasan')) {
                    $table->string('logo_yayasan')->nullable()->after('stempel_sekolah');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_sts_summaries')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                foreach (['ekstrakurikuler', 'keputusan_status', 'keputusan_kelas'] as $col) {
                    if (Schema::hasColumn('student_sts_summaries', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('rapor_settings') && Schema::hasColumn('rapor_settings', 'logo_yayasan')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                $table->dropColumn('logo_yayasan');
            });
        }
    }
};
