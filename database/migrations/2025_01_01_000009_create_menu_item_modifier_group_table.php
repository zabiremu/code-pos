<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pivot: which modifier groups apply to which menu items. */
    public function up(): void
    {
        Schema::create('menu_item_modifier_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->unique(['menu_item_id', 'modifier_group_id'], 'menu_item_modifier_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifier_group');
    }
};
