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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('language')->default('pt_BR');
            $table->string('category'); // marketing, utility, authentication
            $table->text('body');
            $table->json('examples');
            $table->json('components');

            $table->json('payload');
            $table->string('status')->default('pending'); // Status: pending, approved, rejected, in_appeal
            $table->text('rejection_reason')->nullable();
            $table->string('waba_template_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
