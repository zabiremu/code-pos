<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Goods a customer brings back. The refund is either money handed back
     * (cash/card/mobile/other) or, for a bill with money still owed, taken
     * off that bill's due ("adjust_due").
     */
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 30)->nullable()->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('return_date');
            $table->string('refund_method', 20); // cash, card, mobile_wallet, other, adjust_due
            $table->boolean('restock')->default(true);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_refund', 12, 2); // per unit, including its share of tax and discount
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('returned_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn('returned_quantity'));
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
