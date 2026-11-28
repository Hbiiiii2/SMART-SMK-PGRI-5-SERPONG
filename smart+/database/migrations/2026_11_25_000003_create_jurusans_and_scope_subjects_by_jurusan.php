<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jurusans')) {
            Schema::create('jurusans', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('singkatan', 20)->nullable()->index();
                $table->string('nama', 150);
                $table->string('dapodik_jurusan_id', 36)->nullable()->unique();
                $table->text('deskripsi')->nullable();
                $table->boolean('is_aktif')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('rombels', function (Blueprint $table) {
            if (!Schema::hasColumn('rombels', 'jurusan_id')) {
                $table->foreignId('jurusan_id')
                    ->nullable()
                    ->after('tingkat')
                    ->constrained('jurusans')
                    ->nullOnDelete();
            }
        });

        Schema::table('mata_pelajaran', function (Blueprint $table) {
            if (!Schema::hasColumn('mata_pelajaran', 'kelompok')) {
                $table->string('kelompok', 20)->default('umum')->after('nama')->index();
            }
            if (!Schema::hasColumn('mata_pelajaran', 'jurusan_id')) {
                $table->foreignId('jurusan_id')
                    ->nullable()
                    ->after('kelompok')
                    ->constrained('jurusans')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('mata_pelajaran', 'tingkat')) {
                $table->text('tingkat')->nullable()->after('jurusan_id');
            }
            if (!Schema::hasColumn('mata_pelajaran', 'urutan')) {
                $table->unsignedSmallInteger('urutan')->default(100)->after('tingkat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mata_pelajaran', function (Blueprint $table) {
            if (Schema::hasColumn('mata_pelajaran', 'jurusan_id')) {
                $table->dropConstrainedForeignId('jurusan_id');
            }
            foreach (['urutan', 'tingkat', 'kelompok'] as $col) {
                if (Schema::hasColumn('mata_pelajaran', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('rombels', function (Blueprint $table) {
            if (Schema::hasColumn('rombels', 'jurusan_id')) {
                $table->dropConstrainedForeignId('jurusan_id');
            }
        });

        Schema::dropIfExists('jurusans');
    }
};
