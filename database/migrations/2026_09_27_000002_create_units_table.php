<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Units of measure a product is sold and bought in (Piece, Kilogram, Box...). */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('short_name', 20);          // pcs, kg, box
            $table->boolean('allow_decimal')->default(false); // 1.5 kg yes, 1.5 pcs no
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
