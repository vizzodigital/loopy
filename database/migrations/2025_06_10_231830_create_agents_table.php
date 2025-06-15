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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('model')->default('gpt-3.5-turbo');
            $table->decimal('temperature', 3, 2)->default(0.7); // Criatividade
            $table->decimal('top_p', 3, 2)->nullable(); // Alternativa à temperature
            $table->decimal('frequency_penalty', 3, 2)->default(0.0);
            $table->decimal('presence_penalty', 3, 2)->default(0.0);
            $table->integer('max_tokens')->default(1000);
            $table->text('system_prompt')->nullable(); // Define a "personalidade" do agente
            $table->boolean('is_test')->default(false); // Se o agente foi testado
            $table->boolean('is_default')->default(false); // Se o agente está ativo

            $table->timestamps();
        });

        Schema::create('agent_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_store');
        Schema::dropIfExists('agents');
    }
};
