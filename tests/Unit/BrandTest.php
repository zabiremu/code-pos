<?php

namespace Tests\Unit;

use App\Support\Brand;
use PHPUnit\Framework\TestCase;

class BrandTest extends TestCase
{
    public function test_placeholder_names_fall_back_to_the_product_name(): void
    {
        foreach (['POS', 'pos', ' Restaurant POS ', 'Laravel', '', null] as $name) {
            $this->assertSame('ShopPulse POS', Brand::resolve($name));
        }
    }

    public function test_real_shop_names_are_kept(): void
    {
        $this->assertSame('Rahim Store', Brand::resolve('Rahim Store'));
        $this->assertSame('City POS Mart', Brand::resolve('City POS Mart'));
    }
}
