<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('tenant_id')
                  ->constrained('branches')->onDelete('cascade');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('tenant_id')
                  ->constrained('branches')->onDelete('cascade');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('tenant_id')
                  ->constrained('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
