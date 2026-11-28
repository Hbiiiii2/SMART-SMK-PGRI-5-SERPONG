<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'code' => 'tugas',
                'name' => 'Tugas',
                'default_count' => 1,
                'default_weight' => 40.00,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'code' => 'sh',
                'name' => 'Sumatif Harian / Projek / UH',
                'default_count' => 1,
                'default_weight' => 60.00,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'code' => 'asat',
                'name' => 'Nilai Murni ASAT (UAS)',
                'default_count' => 1,
                'default_weight' => 100.00,
                'is_active' => true,
                'order' => 3,
            ],
            // Legacy components preserved (inactive) for backward compatibility with historical items & tests
            [
                'code' => 'uh',
                'name' => 'Ulangan Harian',
                'default_count' => 1,
                'default_weight' => 20.00,
                'is_active' => false,
                'order' => 10,
            ],
            [
                'code' => 'praktek',
                'name' => 'Praktek',
                'default_count' => 1,
                'default_weight' => 15.00,
                'is_active' => false,
                'order' => 11,
            ],
            [
                'code' => 'projek',
                'name' => 'Projek',
                'default_count' => 1,
                'default_weight' => 15.00,
                'is_active' => false,
                'order' => 12,
            ],
            [
                'code' => 'portofolio',
                'name' => 'Portofolio',
                'default_count' => 1,
                'default_weight' => 10.00,
                'is_active' => false,
                'order' => 13,
            ],
            [
                'code' => 'sts',
                'name' => 'STS 1',
                'default_count' => 1,
                'default_weight' => 20.00,
                'is_active' => false,
                'order' => 14,
            ],
        ];

        foreach ($components as $component) {
            DB::table('assessment_components')->updateOrInsert(
                ['code' => $component['code']],
                array_merge($component, ['updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}
