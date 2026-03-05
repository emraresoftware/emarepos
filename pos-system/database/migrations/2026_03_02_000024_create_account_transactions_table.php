<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('external_id')->nullable()->index();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
