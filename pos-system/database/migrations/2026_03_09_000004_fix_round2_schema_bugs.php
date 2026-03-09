<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Derin Analiz Round 2 — Schema düzeltmeleri
 * BUG-1: tenants.status enum'a 'trial' ekle (string'e çevir)
 */
return new class extends Migration
{
    public function up(): void
    {
        // tenants.status — enum → string(20) ('trial' değeri eklenebilsin)
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        // Geri almak isterseniz enum'a döndürün
    }
};
