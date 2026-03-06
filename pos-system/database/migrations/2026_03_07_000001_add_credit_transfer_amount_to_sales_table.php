<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('credit_amount', 14, 2)->default(0)->after('card_amount');
            $table->decimal('transfer_amount', 14, 2)->default(0)->after('credit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['credit_amount', 'transfer_amount']);
        });
    }
};
