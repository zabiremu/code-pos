<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A physical dining table on a floor. Status drives the POS floor-plan view. */
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedTinyInteger('seats')->default(2);
            $table->enum('status', ['free', 'occupied', 'reserved', 'billed'])->default('free');
            $table->unsignedSmallInteger('pos_x')->default(0);
            $table->unsignedSmallInteger('pos_y')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
