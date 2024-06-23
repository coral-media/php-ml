<?php

declare(strict_types=1);

namespace Phpml\Tests\NeuralNetwork\ActivationFunction;

use Phpml\NeuralNetwork\ActivationFunction\Sigmoid;
use PHPUnit\Framework\TestCase;

class SigmoidTest extends TestCase
{
    /**
     * @dataProvider sigmoidProvider
     *
     * @param float|int $value
     */
    public function testSigmoidActivationFunction(float $beta, float $expected, $value): void
    {
        $sigmoid = new Sigmoid($beta);

        self::assertEqualsWithDelta($expected, $sigmoid->compute($value), 0.001);
    }

    public function sigmoidProvider(): array
    {
        return [
            [1.0, 1, 7.25],
            [2.0, 1, 3.75],
            [1.0, 0.5, 0],
            [0.5, 0.5, 0],
            [1.0, 0, -7.25],
            [2.0, 0, -3.75],
        ];
    }

    /**
     * @dataProvider sigmoidDerivativeProvider
     *
     * @param float|int $value
     */
    public function testSigmoidDerivative(float $beta, float $expected, $value): void
    {
        $sigmoid = new Sigmoid($beta);
        $activatedValue = $sigmoid->compute($value);
        self::assertEqualsWithDelta($expected, $sigmoid->differentiate($value, $activatedValue), 0.001);
    }

    public function sigmoidDerivativeProvider(): array
    {
        return [
            [1.0, 0, -10],
            [1, 0.006, -5],
            [1.0, 0.25, 0],
            [1, 0.006, 5],
            [1.0, 0, 10],
            [2.0, 0.25, 0],
            [0.5, 0.246, 0.5],
            [0.5, 0.241, 0.75],
        ];
    }
}
