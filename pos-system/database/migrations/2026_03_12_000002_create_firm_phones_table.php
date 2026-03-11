<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
            $table->string('phone');
            $table->string('type')->default('mobile'); // mobile | landline | other
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Mevcut firms.phone verisini firm_phones'a taşı
        \DB::statement("
            INSERT INTO firm_phones (firm_id, phone, type, is_primary, created_at, updated_at)
            SELECT id, phone, 'mobile', 1, created_at, updated_at
            FROM firms
            WHERE phone IS NOT NULL AND phone != ''
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_phones');
    }
};
