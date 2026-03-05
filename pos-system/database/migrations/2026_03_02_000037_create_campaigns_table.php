<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['discount', 'coupon', 'buy_x_get_y', 'bundle', 'loyalty_multiplier', 'free_delivery', 'happy_hour'])->default('discount');
            $table->enum('status', ['draft', 'active', 'paused', 'expired', 'cancelled'])->default('draft');
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'buy_x_get_y'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('min_purchase_amount', 12, 2)->nullable();
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('per_customer_limit')->nullable();
            $table->string('coupon_code')->unique()->nullable();
            $table->json('target_products')->nullable();
            $table->json('target_categories')->nullable();
            $table->json('target_segments')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
