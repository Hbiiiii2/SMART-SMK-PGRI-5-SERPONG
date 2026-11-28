<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('siswa', 'tahun_lulus')) {
                $table->unsignedSmallInteger('tahun_lulus')->nullable()->after('status_siswa')->index();
            }
            if (!Schema::hasColumn('siswa', 'tahun_ajaran_lulus_id')) {
                $table->foreignId('tahun_ajaran_lulus_id')
                    ->nullable()
                    ->after('tahun_lulus')
                    ->constrained('tahun_ajaran')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasTable('rapor_archives')) {
            Schema::create('rapor_archives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
                $table->string('semester', 20);
                $table->string('jenis_rapor', 20);
                $table->string('kelas', 50);
                $table->foreignId('rombel_id')->nullable()->constrained('rombels')->nullOnDelete();
                $table->string('archive_number', 100)->unique();
                $table->uuid('verification_token')->unique();
                $table->string('document_hash', 64);
                $table->unsignedSmallInteger('version')->default(1);
                $table->longText('payload');
                $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finalized_at')->nullable();
                $table->unsignedInteger('reprint_count')->default(0);
                $table->timestamp('last_reprinted_at')->nullable();
                $table->timestamps();

                $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester', 'jenis_rapor'], 'rapor_archives_unique_slot');
                $table->index(['tahun_ajaran_id', 'semester', 'kelas', 'jenis_rapor'], 'rapor_archives_period_kelas_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_archives');

        Schema::table('siswa', function (Blueprint $table) {
            if (Schema::hasColumn('siswa', 'tahun_ajaran_lulus_id')) {
                $table->dropConstrainedForeignId('tahun_ajaran_lulus_id');
            }
            if (Schema::hasColumn('siswa', 'tahun_lulus')) {
                $table->dropColumn('tahun_lulus');
            }
        });
    }
};
