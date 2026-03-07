<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'country_of_origin')) {
                $table->string('country_of_origin', 100)->nullable()->after('image_url');
            }
            // critical_stock already may exist; safe guard
            if (!Schema::hasColumn('products', 'critical_stock')) {
                $table->decimal('critical_stock', 14, 2)->default(0)->after('stock_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['country_of_origin']);
        });
    }
};
