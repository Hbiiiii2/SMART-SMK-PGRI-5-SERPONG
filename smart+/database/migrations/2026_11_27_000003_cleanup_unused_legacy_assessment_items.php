<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_items') || !Schema::hasTable('student_assessment_scores')) {
            return;
        }

        DB::table('assessment_items')
            ->whereNull('weight_group')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('student_assessment_scores')
                    ->whereColumn('student_assessment_scores.assessment_item_id', 'assessment_items.id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Legacy un-scored auto-provisioned items do not need restoration.
    }
};
