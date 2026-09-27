<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Support\Code128;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class LabelsAndCsvTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_code128_known_encodings(): void
    {
        // Start B, B, W, -, 5, 0, 0, checksum, stop - cross-checked with python-barcode and zbar.
        $bw = Code128::encode('BW-500');
        $this->assertSame([104, 34, 55, 13, 21, 16, 16], array_slice($bw, 0, 7));
        $this->assertSame(106, last($bw));
        $this->assertSame(105, Code128::encode('12345678')[0]); // all digits -> set C
        $this->assertStringContainsString('<svg', Code128::svg('NB-001'));
    }

    public function test_generate_skus_and_print_labels(): void
    {
        $admin = $this->staff('manager');
        $a = Product::create(['name' => 'Tea', 'base_price' => 50, 'is_available' => true]);
        $b = Product::create(['name' => 'Coffee', 'sku' => 'CF-1', 'base_price' => 90, 'is_available' => true]);

        $this->actingAs($admin)->get(route('admin.labels.index', ['products' => $a->id]))->assertOk();

        // Can't print a product that has no barcode yet.
        $this->actingAs($admin)->post(route('admin.labels.print'), [
            'template' => 'a4_40', 'items' => [['product_id' => $a->id, 'qty' => 2]],
        ])->assertSessionHasErrors('items');

        $this->actingAs($admin)->post(route('admin.labels.generate-skus'))->assertSessionHas('status');
        $this->assertSame('2'.str_pad((string) $a->id, 8, '0', STR_PAD_LEFT), $a->fresh()->sku);
        $this->assertSame('CF-1', $b->fresh()->sku);

        $this->actingAs($admin)->post(route('admin.labels.print'), [
            'template' => 'roll_50x25', 'show_name' => '1', 'show_price' => '1',
            'items' => [['product_id' => $a->id, 'qty' => 2], ['product_id' => $b->id, 'qty' => 1]],
        ])->assertOk()->assertSee('Print 3 labels')->assertSee('CF-1');
    }

    public function test_export_and_template_download(): void
    {
        Product::create(['name' => 'Tea', 'sku' => 'T1', 'base_price' => 50, 'purchase_price' => 30, 'is_available' => true]);
        $admin = $this->staff('admin');

        $csv = $this->actingAs($admin)->get(route('admin.products.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('sku,name,category', $csv);
        $this->assertStringContainsString('T1,Tea', $csv);

        $this->actingAs($admin)->get(route('admin.products.import.template'))->assertOk();
        $this->actingAs($admin)->get(route('admin.products.import'))->assertOk();
    }

    public function test_import_creates_updates_and_makes_lookups(): void
    {
        Product::create(['name' => 'Old tea', 'sku' => 'T1', 'base_price' => 50, 'purchase_price' => 30, 'is_available' => true]);

        $csv = "\xEF\xBB\xBFSKU,Name,Category,Brand,Unit,Cost,Price,MRP,track_stock,opening_stock\n"
            ."T1,Green tea,Beverages,Ispahani,pcs,,55,,yes,99\n"
            ."R5,Rice 5kg,Groceries,,kg,400,480,500,yes,12\n"
            .",Loose sugar,Groceries,,kg,90,110,,no,\n";

        $this->actingAs($this->staff('admin'))->post(route('admin.products.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHas('status', 'Import done: 2 added, 1 updated.');

        $tea = Product::where('sku', 'T1')->first();
        $this->assertSame('Green tea', $tea->name);
        $this->assertEquals(55, (float) $tea->base_price);
        $this->assertEquals(30, (float) $tea->purchase_price); // blank cost kept the old value
        $this->assertEquals(0, (float) $tea->stock_quantity);  // opening stock ignored on update

        $rice = Product::where('sku', 'R5')->first();
        $this->assertEquals(12, (float) $rice->stock_quantity);
        $this->assertEquals(500, (float) $rice->regular_price);
        $this->assertTrue(Unit::find($rice->unit_id)->allow_decimal);
        $this->assertSame(1, Category::where('name', 'Groceries')->count());
        $this->assertTrue(Brand::where('name', 'Ispahani')->exists());
        $this->assertFalse(Product::where('name', 'Loose sugar')->first()->track_stock);
    }

    public function test_bad_rows_import_nothing(): void
    {
        $csv = "name,sale_price,available\nGood,10,yes\n,20,yes\nBad price,abc,yes\nOdd,5,maybe\n";

        $this->actingAs($this->staff('admin'))->post(route('admin.products.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('p.csv', $csv),
        ])->assertSessionHasErrors('file')->assertSessionHas('importErrors', fn ($errors) => count($errors) === 3);

        $this->assertSame(0, Product::count());
    }
}
