<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What each sold item cost the shop at the moment of sale, so profit
     * reports stay right when purchase prices change later. Older rows are
     * filled with today's purchase price - the best information available.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('unit_price');
        });

        DB::table('sale_items')->orderBy('id')->chunkById(500, function ($rows) {
            $costs = DB::table('products')->whereIn('id', $rows->pluck('product_id'))->pluck('purchase_price', 'id');
            foreach ($rows as $row) {
                DB::table('sale_items')->where('id', $row->id)->update(['unit_cost' => $costs[$row->product_id] ?? 0]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
    }
};
