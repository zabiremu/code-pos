<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * type becomes a plain string so new movement kinds (opening,
     * goods_received, purchase_return) don't need an enum change each time.
     * reference_* points at the document that caused the movement.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('type', 30)->change();
            $table->decimal('qty', 14, 3)->change();
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropMorphs('reference');
        });
    }
};
