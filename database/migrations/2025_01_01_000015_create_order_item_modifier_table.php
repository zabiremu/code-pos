<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Modifiers picked for one order item, with a price snapshot. */
    public function up(): void
    {
        Schema::create('order_item_modifier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_id')->constrained();
            $table->decimal('price_delta', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_modifier');
    }
};
