<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mekan (region) tablosuna tasarım kolonları
        Schema::table('table_regions', function (Blueprint $table) {
            $table->string('bg_color', 20)->default('#f0f9ff')->after('is_active');
            $table->string('icon', 50)->nullable()->after('bg_color');   // fa icon adı
            $table->text('description')->nullable()->after('icon');
        });

        // Masa tablosuna konum ve şekil kolonları
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->float('pos_x')->default(10)->after('is_active');   // % cinsinden (0-95)
            $table->float('pos_y')->default(10)->after('pos_x');       // % cinsinden (0-90)
            $table->enum('shape', ['square', 'circle', 'rectangle'])->default('square')->after('pos_y');
            $table->string('color', 20)->nullable()->after('shape');   // opsiyonel özel renk
        });
    }

    public function down(): void
    {
        Schema::table('table_regions', function (Blueprint $table) {
            $table->dropColumn(['bg_color', 'icon', 'description']);
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y', 'shape', 'color']);
        });
    }
};
