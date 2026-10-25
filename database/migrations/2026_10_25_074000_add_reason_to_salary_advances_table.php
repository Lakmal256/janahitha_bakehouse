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
        if (! Schema::hasColumn('salary_advances', 'reason')) {
            Schema::table('salary_advances', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('salary_month');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('salary_advances', 'reason')) {
            Schema::table('salary_advances', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
