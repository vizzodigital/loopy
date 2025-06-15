<?php

declare(strict_types = 1);

use App\Enums\IntegrationTypeEnum;
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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->uuid('webhook')->unique();
            $table->enum('type', IntegrationTypeEnum::getValues());
            $table->json('configs');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['store_id', 'platform_id']);
            $table->index(['store_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
