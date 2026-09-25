<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Recipe line: how much of each ingredient one unit of a menu item consumes. */
    public function up(): void
    {
        Schema::create('menu_item_ingredient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 10, 3);
            $table->unique(['menu_item_id', 'ingredient_id'], 'menu_item_ingredient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_ingredient');
    }
};
