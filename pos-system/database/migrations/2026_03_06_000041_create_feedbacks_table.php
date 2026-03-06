<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('session_key', 64)->nullable()->index(); // anonim user takibi
            $table->string('user_name', 100)->nullable();
            $table->enum('category', ['bug', 'suggestion', 'question', 'other'])->default('other');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->text('message');
            $table->string('page_url', 500)->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
