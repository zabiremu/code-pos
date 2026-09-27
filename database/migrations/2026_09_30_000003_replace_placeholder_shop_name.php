<?php

use App\Support\Brand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Earlier versions pre-filled Settings > Shop name with whatever .env said
     * ("POS", "Restaurant POS"), so saving Settings stored that placeholder.
     * Replace it with the product name; real shop names are left alone.
     */
    public function up(): void
    {
        $current = DB::table('settings')->where('key', 'shop_name')->value('value');

        if ($current === null || Brand::isPlaceholder($current)) {
            DB::table('settings')->updateOrInsert(['key' => 'shop_name'], ['value' => Brand::NAME, 'updated_at' => now(), 'created_at' => now()]);
            Cache::forget('app_settings');
        }
    }

    public function down(): void
    {
        //
    }
};
