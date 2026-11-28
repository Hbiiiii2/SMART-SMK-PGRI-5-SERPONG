<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('assessment_items')) {
            Schema::table('assessment_items', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_items', 'daily_block')) {
                    $table->unsignedSmallInteger('daily_block')->nullable()->after('assessment_component_id');
                }
                if (!Schema::hasColumn('assessment_items', 'weight_group')) {
                    $table->string('weight_group', 20)->nullable()->after('daily_block');
                }
            });

            try {
                Schema::table('assessment_items', function (Blueprint $table) {
                    $table->index(
                        ['mata_pelajaran_id', 'tahun_ajaran_id', 'semester', 'daily_block'],
                        'idx_ai_mapel_ta_sem_block'
                    );
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }

        if (Schema::hasTable('subject_grade_summaries')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('subject_grade_summaries', 'daily_scores')) {
                    $table->json('daily_scores')->nullable()->after('portofolio_avg');
                }
                if (!Schema::hasColumn('subject_grade_summaries', 'sh_avg')) {
                    $table->decimal('sh_avg', 5, 2)->nullable()->after('daily_scores');
                }
                if (!Schema::hasColumn('subject_grade_summaries', 'asat_score')) {
                    $table->decimal('asat_score', 5, 2)->nullable()->after('sh_avg');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assessment_items')) {
            try {
                Schema::table('assessment_items', function (Blueprint $table) {
                    $table->dropIndex('idx_ai_mapel_ta_sem_block');
                });
            } catch (\Throwable $e) {
                // Ignore if missing
            }

            Schema::table('assessment_items', function (Blueprint $table) {
                $cols = array_values(array_filter(
                    ['daily_block', 'weight_group'],
                    fn ($col) => Schema::hasColumn('assessment_items', $col)
                ));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('subject_grade_summaries')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                $cols = array_values(array_filter(
                    ['daily_scores', 'sh_avg', 'asat_score'],
                    fn ($col) => Schema::hasColumn('subject_grade_summaries', $col)
                ));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
