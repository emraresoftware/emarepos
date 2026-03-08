<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // BUG-1: account_transactions.customer_id nullable yap (firma ödemeleri için)
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // BUG-5: purchase_invoices.invoice_no nullable yap
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->change();
        });

        // BUG-2+7: purchase_invoices.status — enum yerine string yap (enum constraint sorunlu)
        // SQLite enum desteklemez, MariaDB'de de ALTER ENUM problemli
        // Güvenli çözüm: string'e çevir
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
        });

        // BUG-4: order_items.status — enum'a 'paid' eklenmeli
        // Yine güvenli çözüm: string'e çevir
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Geri alma sadece genel yapıyı korur
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};
