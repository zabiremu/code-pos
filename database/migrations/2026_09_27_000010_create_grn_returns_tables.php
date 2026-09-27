<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Goods sent back to the supplier from a GRN (damaged, wrong item, expired...). */
    public function up(): void
    {
        Schema::create('grn_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 30)->nullable()->unique();
            $table->foreignId('grn_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('grn_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grn_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_return_items');
        Schema::dropIfExists('grn_returns');
    }
};
