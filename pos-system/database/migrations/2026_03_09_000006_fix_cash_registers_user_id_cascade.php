<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            // Kullanıcı silindiğinde kasa geçmişi yok olmasın;
            // user_id NULL olsun (SET NULL), CASCADE değil.
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            // Geri alırken NULL olmayan kayıtları olan satırlar problem çıkarabilir;
            // sadece FK'yı geri yükle, NULL-ability'yi tersine çevirmiyoruz.
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
