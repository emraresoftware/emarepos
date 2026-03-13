<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('firms', 'credit_limit')) {
            Schema::table('firms', function (Blueprint $table) {
                $table->decimal('credit_limit', 15, 2)->default(0)->after('balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('firms', 'credit_limit')) {
            Schema::table('firms', function (Blueprint $table) {
                $table->dropColumn('credit_limit');
            });
        }
    }
};