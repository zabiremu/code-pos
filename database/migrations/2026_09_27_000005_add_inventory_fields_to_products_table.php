<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * base_price stays the SALE price (what the POS charges). Added:
     * purchase_price (cost, updated from the latest goods received) and
     * regular_price (MRP / price before discount, shown struck through).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('unit_id')->constrained()->nullOnDelete();
            $table->decimal('purchase_price', 12, 2)->default(0)->after('base_price');
            $table->decimal('regular_price', 12, 2)->nullable()->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['purchase_price', 'regular_price']);
        });
    }
};
