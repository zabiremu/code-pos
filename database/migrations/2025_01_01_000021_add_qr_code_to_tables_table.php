<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Backs the QR-code customer self-ordering feature: each table gets a
 * random, non-sequential token (never the numeric id, so a customer can't
 * guess other tables' URLs) that resolves to a public "scan to order" page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->string('qr_code', 40)->nullable()->unique()->after('label');
        });

        // Backfill tables that existed before this column did - new tables
        // get one automatically via DiningTable::booted()'s creating hook.
        foreach (DB::table('tables')->whereNull('qr_code')->get(['id']) as $row) {
            DB::table('tables')->where('id', $row->id)->update(['qr_code' => Str::random(32)]);
        }
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn('qr_code');
        });
    }
};
