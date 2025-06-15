<?php

declare(strict_types = 1);

use App\Enums\CartStatusEnum;
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
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('abandonment_reason_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('external_cart_id')->nullable(); // ID externo do carrinho
            $table->json('cart_data')->nullable();
            $table->json('customer_data')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('status', CartStatusEnum::getValues())->default(CartStatusEnum::ABANDONED);
            $table->timestamps();

            $table->index(['store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
