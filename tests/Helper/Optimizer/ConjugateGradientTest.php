<?php

declare(strict_types=1);

namespace Phpml\Tests\Helper\Optimizer;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Helper\Optimizer\ConjugateGradient;
use PHPUnit\Framework\TestCase;

class ConjugateGradientTest extends TestCase
{
    public function testRunOptimization(): void
    {
        // 200 samples from y = -1 + 2x (i.e. theta = [-1, 2])
        $samples = [];
        $targets = [];
        for ($i = -100; $i <= 100; ++$i) {
            $x = $i / 100;
            $samples[] = [$x];
            $targets[] = -1 + 2 * $x;
        }

        $callback = static function ($theta, $sample, $target): array {
            $y = $theta[0] + $theta[1] * $sample[0];
            $cost = (($y - $target) ** 2) / 2;
            $grad = $y - $target;

            return [$cost, $grad];
        };

        $optimizer = new ConjugateGradient(1);

        $theta = $optimizer->runOptimization($samples, $targets, $callback);

        self::assertEqualsWithDelta([-1, 2], $theta, 0.1);
    }

    public function testRunOptimizationWithCustomInitialTheta(): void
    {
        // 200 samples from y = -1 + 2x (i.e. theta = [-1, 2])
        $samples = [];
        $targets = [];
        for ($i = -100; $i <= 100; ++$i) {
            $x = $i / 100;
            $samples[] = [$x];
            $targets[] = -1 + 2 * $x;
        }

        $callback = static function ($theta, $sample, $target): array {
            $y = $theta[0] + $theta[1] * $sample[0];
            $cost = (($y - $target) ** 2) / 2;
            $grad = $y - $target;

            return [$cost, $grad];
        };

        $optimizer = new ConjugateGradient(1);
        // set very weak theta to trigger very bad result
        $optimizer->setTheta([0.0000001, 0.0000001]);

        $theta = $optimizer->runOptimization($samples, $targets, $callback);

        self::assertEqualsWithDelta([-1.087708, 2.212034], $theta, 0.000001);
    }

    public function testRunOptimization2Dim(): void
    {
        // 100 samples from y = -1 + 2x0 - 3x1 (i.e. theta = [-1, 2, -3])
        $samples = [];
        $targets = [];
        for ($i = 0; $i < 100; ++$i) {
            $x0 = intval($i / 10) / 10;
            $x1 = ($i % 10) / 10;
            $samples[] = [$x0, $x1];
            $targets[] = -1 + 2 * $x0 - 3 * $x1;
        }

        $callback = static function ($theta, $sample, $target): array {
            $y = $theta[0] + $theta[1] * $sample[0] + $theta[2] * $sample[1];
            $cost = (($y - $target) ** 2) / 2;
            $grad = $y - $target;

            return [$cost, $grad];
        };

        $optimizer = new ConjugateGradient(2);
        $optimizer->setChangeThreshold(1e-6);

        $theta = $optimizer->runOptimization($samples, $targets, $callback);

        self::assertEqualsWithDelta([-1, 2, -3], $theta, 0.1);
    }

    public function testThrowExceptionOnInvalidTheta(): void
    {
        $opimizer = new ConjugateGradient(2);

        $this->expectException(InvalidArgumentException::class);
        $opimizer->setTheta([0.15]);
    }
}
