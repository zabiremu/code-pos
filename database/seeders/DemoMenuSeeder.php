<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Floor;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Sample menu + floor plan so a fresh install has something to demo immediately. */
class DemoMenuSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Branch'], ['tax_rate' => 5, 'currency' => 'USD']);

        $floor = Floor::firstOrCreate(['branch_id' => $branch->id, 'name' => 'Ground Floor']);
        foreach (range(1, 6) as $n) {
            $floor->tables()->firstOrCreate(['label' => "T{$n}"], ['seats' => $n <= 2 ? 2 : 4, 'status' => 'free']);
        }

        $spiceLevel = ModifierGroup::firstOrCreate(['name' => 'Spice Level'], ['max_selectable' => 1]);
        foreach (['Mild' => 0, 'Medium' => 0, 'Hot' => 0.5] as $name => $delta) {
            $spiceLevel->modifiers()->firstOrCreate(['name' => $name], ['price_delta' => $delta]);
        }

        $addOns = ModifierGroup::firstOrCreate(['name' => 'Add-ons'], ['max_selectable' => 3]);
        foreach (['Extra cheese' => 1.5, 'Extra sauce' => 0.5] as $name => $delta) {
            $addOns->modifiers()->firstOrCreate(['name' => $name], ['price_delta' => $delta]);
        }

        $chicken = Ingredient::firstOrCreate(['name' => 'Chicken'], ['unit' => 'kg', 'stock_qty' => 20, 'low_stock_threshold' => 3]);
        $cheese = Ingredient::firstOrCreate(['name' => 'Cheese'], ['unit' => 'kg', 'stock_qty' => 10, 'low_stock_threshold' => 2]);

        $mains = Category::firstOrCreate(['slug' => 'mains'], ['name' => 'Mains', 'sort_order' => 1]);
        $drinks = Category::firstOrCreate(['slug' => 'drinks'], ['name' => 'Drinks', 'sort_order' => 2]);

        $burger = MenuItem::firstOrCreate(
            ['name' => 'Grilled Chicken Burger'],
            ['category_id' => $mains->id, 'base_price' => 8.50, 'is_available' => true]
        );
        $burger->modifierGroups()->syncWithoutDetaching([$spiceLevel->id, $addOns->id]);
        $burger->ingredients()->syncWithoutDetaching([
            $chicken->id => ['qty' => 0.2],
            $cheese->id => ['qty' => 0.05],
        ]);
        $burger->variants()->firstOrCreate(['name' => 'Regular'], ['price_delta' => 0, 'is_default' => true]);
        $burger->variants()->firstOrCreate(['name' => 'Large'], ['price_delta' => 2.0]);

        MenuItem::firstOrCreate(
            ['name' => 'Iced Lemon Tea'],
            ['category_id' => $drinks->id, 'base_price' => 2.50, 'is_available' => true]
        );

        Discount::firstOrCreate(
            ['code' => 'WELCOME10'],
            ['name' => 'Welcome 10%', 'type' => 'percent', 'value' => 10, 'is_active' => true]
        );
    }
}
