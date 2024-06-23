<?php

declare(strict_types=1);

namespace Phpml\Tests\Math;

use Phpml\Math\Product;
use PHPUnit\Framework\TestCase;
use stdClass;

class ProductTest extends TestCase
{
    public function testScalarProduct(): void
    {
        self::assertEquals(10, Product::scalar([2, 3], [-1, 4]));
        self::assertEquals(-0.1, Product::scalar([1, 4, 1], [-2, 0.5, -0.1]));
        self::assertEquals(8, Product::scalar([2], [4]));

        //test for non numeric values
        self::assertEquals(0, Product::scalar(['', null, [], new stdClass()], [null]));
    }
}
