<?php

declare(strict_types = 1);

use App\Enums\ConversationSenderTypeEnum;
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
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ConversationSenderTypeEnum::getValues())->index();
            $table->text('content');
            $table->json('payload')->nullable();
            $table->boolean('was_read')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['store_id']);
            $table->index(['conversation_id', 'sender_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};
