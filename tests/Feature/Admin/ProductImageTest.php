<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
    }

    public function test_photo_is_stored_on_create_and_replaced_on_update(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Mug', 'base_price' => '250', 'is_available' => '1',
            'image' => UploadedFile::fake()->image('mug.jpg', 1600, 1200),
        ])->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $first = $product->image_path;
        $this->assertNotNull($first);
        Storage::disk('uploads')->assertExists($first);
        $this->assertStringContainsString('/uploads/products/', $product->imageUrl());

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Mug', 'base_price' => '250',
            'image' => UploadedFile::fake()->image('mug2.png', 400, 400),
        ])->assertSessionHasNoErrors();

        Storage::disk('uploads')->assertMissing($first);
        Storage::disk('uploads')->assertExists($product->fresh()->image_path);
    }

    public function test_photo_can_be_removed(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Mug', 'base_price' => '250', 'image' => UploadedFile::fake()->image('mug.jpg'),
        ]);
        $product = Product::firstOrFail();
        $path = $product->image_path;

        $this->actingAs($admin)->put(route('admin.products.update', $product), ['name' => 'Mug', 'base_price' => '250', 'remove_image' => '1']);

        $this->assertNull($product->fresh()->image_path);
        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_non_images_are_rejected(): void
    {
        $this->actingAs($this->staff('admin'))->post(route('admin.products.store'), [
            'name' => 'Mug', 'base_price' => '250', 'image' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }
}
