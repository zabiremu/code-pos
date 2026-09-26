<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Seeder;

/** Sample catalog so a fresh install has something to demo immediately. */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(['name' => 'Main Branch'], ['tax_rate' => 5, 'currency' => 'USD']);

        $groceries = Category::firstOrCreate(['slug' => 'groceries'], ['name' => 'Groceries', 'sort_order' => 1]);
        $beverages = Category::firstOrCreate(['slug' => 'beverages'], ['name' => 'Beverages', 'sort_order' => 2]);

        Product::firstOrCreate(
            ['name' => 'Notebook'],
            [
                'category_id' => $groceries->id,
                'sku' => 'NB-001',
                'base_price' => 3.50,
                'track_stock' => true,
                'stock_quantity' => 50,
                'low_stock_threshold' => 10,
                'is_available' => true,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Bottled Water 500ml'],
            [
                'category_id' => $beverages->id,
                'sku' => 'BW-500',
                'base_price' => 1.00,
                'track_stock' => true,
                'stock_quantity' => 100,
                'low_stock_threshold' => 20,
                'is_available' => true,
            ]
        );

        Discount::firstOrCreate(
            ['code' => 'WELCOME10'],
            ['name' => 'Welcome 10%', 'type' => 'percent', 'value' => 10, 'is_active' => true]
        );
    }
}
