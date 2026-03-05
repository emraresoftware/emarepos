<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('device_type')->index();
            $table->string('manufacturer')->index();
            $table->string('model')->nullable();
            $table->string('vendor_id')->nullable()->index();
            $table->string('product_id')->nullable();
            $table->string('protocol')->nullable();
            $table->json('connections')->nullable();
            $table->json('features')->nullable();
            $table->json('specs')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_drivers');
    }
};
