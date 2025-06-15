<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->uuid('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('store_user', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_user');
        Schema::dropIfExists('stores');
    }
};
