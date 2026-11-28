<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mata_pelajaran') && !Schema::hasColumn('mata_pelajaran', 'tampil_di_rapor')) {
            Schema::table('mata_pelajaran', function (Blueprint $table) {
                $table->boolean('tampil_di_rapor')->default(true)->after('urutan')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mata_pelajaran') && Schema::hasColumn('mata_pelajaran', 'tampil_di_rapor')) {
            Schema::table('mata_pelajaran', function (Blueprint $table) {
                $table->dropIndex(['tampil_di_rapor']);
                $table->dropColumn('tampil_di_rapor');
            });
        }
    }
};
