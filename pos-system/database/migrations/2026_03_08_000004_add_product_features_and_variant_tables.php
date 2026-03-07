<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products tablosuna yeni alanlar ekle
        Schema::table('products', function (Blueprint $table) {
            $table->string('stock_code')->nullable()->after('barcode');
            $table->integer('sort_order')->default(0)->after('is_service');
            $table->boolean('show_on_pos')->default(true)->after('sort_order');
        });

        // Ürün Varyant Tanımları (Renk, Beden, Boyut gibi)
        Schema::create('product_variant_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name'); // Renk, Beden, Boyut
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Varyant Değerleri (Kırmızı, Mavi, S, M, L, XL)
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_type_id')->constrained('product_variant_types')->onDelete('cascade');
            $table->string('value'); // Kırmızı, Mavi, S, M, L
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Ürün-Varyant ilişkisi (bir ürünün hangi varyant değerine sahip olduğu)
        Schema::create('product_variant_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_value_id')->constrained('product_variant_values')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'variant_value_id']);
        });

        // Alt Ürün Tanımları (Koli-Paket-Adet ilişkisi)
        Schema::create('product_sub_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('sub_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('multiplier', 10, 2)->default(1); // Ana üründe kaç alt ürün var
            $table->boolean('apply_to_branches')->default(false);
            $table->timestamps();

            $table->unique(['parent_product_id', 'sub_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sub_definitions');
        Schema::dropIfExists('product_variant_assignments');
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variant_types');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_code', 'sort_order', 'show_on_pos']);
        });
    }
};
