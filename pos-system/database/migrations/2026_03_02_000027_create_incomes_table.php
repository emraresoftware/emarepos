<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('external_id')->nullable()->index();
            $table->foreignId('income_expense_type_id')->constrained('income_expense_types')->onDelete('cascade');
            $table->string('type_name')->nullable();
            $table->text('note')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_type')->default('cash');
            $table->date('date');
            $table->time('time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
