<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sales.sold_at — en kritik: dashboard, raporlar, kasa kapatma
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['branch_id', 'sold_at', 'status'], 'sales_branch_sold_status');
        });

        // orders.ordered_at — mutfak ekranı, sipariş listesi, gün sonu
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['branch_id', 'ordered_at'], 'orders_branch_ordered_at');
        });

        // stock_movements — stok geçmişi, rapor
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['branch_id', 'movement_date'], 'stock_movements_branch_date');
            $table->index('product_id', 'stock_movements_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_branch_sold_status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_branch_ordered_at');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_branch_date');
            $table->dropIndex('stock_movements_product_id');
        });
    }
};
