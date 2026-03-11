<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('phone');
            $table->string('type')->default('mobile'); // mobile | landline | other
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Mevcut customers.phone verisini customer_phones'a taşı
        \DB::statement("
            INSERT INTO customer_phones (customer_id, phone, type, is_primary, created_at, updated_at)
            SELECT id, phone, 'mobile', 1, created_at, updated_at
            FROM customers
            WHERE phone IS NOT NULL AND phone != ''
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phones');
    }
};
