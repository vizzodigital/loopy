<?php

declare(strict_types = 1);

use App\Enums\ConversationStatusEnum;
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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('abandoned_cart_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ConversationStatusEnum::getValues())->default(ConversationStatusEnum::OPEN);
            $table->text('system_prompt')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('human_assumed_at')->nullable();
            $table->foreignId('human_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id']);
            $table->index(['abandoned_cart_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
