<?php

declare(strict_types=1);

namespace Phpml\Tests\NeuralNetwork\Node;

use Phpml\NeuralNetwork\Node\Bias;
use PHPUnit\Framework\TestCase;

class BiasTest extends TestCase
{
    public function testBiasOutput(): void
    {
        $bias = new Bias();

        self::assertEquals(1.0, $bias->getOutput());
    }
}
