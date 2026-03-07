<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('firms', function (Blueprint $table) {
            $table->foreignId('firm_group_id')->nullable()->after('tenant_id')->constrained('firm_groups')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropForeign(['firm_group_id']);
            $table->dropColumn('firm_group_id');
        });
        Schema::dropIfExists('firm_groups');
    }
};
