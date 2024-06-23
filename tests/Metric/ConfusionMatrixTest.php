<?php

declare(strict_types=1);

namespace Phpml\Tests\Metric;

use Phpml\Metric\ConfusionMatrix;
use PHPUnit\Framework\TestCase;

class ConfusionMatrixTest extends TestCase
{
    public function testComputeConfusionMatrixOnNumericLabels(): void
    {
        $actualLabels = [2, 0, 2, 2, 0, 1];
        $predictedLabels = [0, 0, 2, 2, 0, 2];

        $confusionMatrix = [
            [2, 0, 0],
            [0, 0, 1],
            [1, 0, 2],
        ];

        self::assertEquals($confusionMatrix, ConfusionMatrix::compute($actualLabels, $predictedLabels));
    }

    public function testComputeConfusionMatrixOnStringLabels(): void
    {
        $actualLabels = ['cat', 'ant', 'cat', 'cat', 'ant', 'bird'];
        $predictedLabels = ['ant', 'ant', 'cat', 'cat', 'ant', 'cat'];

        $confusionMatrix = [
            [2, 0, 0],
            [0, 0, 1],
            [1, 0, 2],
        ];

        self::assertEquals($confusionMatrix, ConfusionMatrix::compute($actualLabels, $predictedLabels));
    }

    public function testComputeConfusionMatrixOnLabelsWithSubset(): void
    {
        $actualLabels = ['cat', 'ant', 'cat', 'cat', 'ant', 'bird'];
        $predictedLabels = ['ant', 'ant', 'cat', 'cat', 'ant', 'cat'];
        $labels = ['ant', 'bird'];

        $confusionMatrix = [
            [2, 0],
            [0, 0],
        ];

        self::assertEquals($confusionMatrix, ConfusionMatrix::compute($actualLabels, $predictedLabels, $labels));

        $labels = ['bird', 'ant'];

        $confusionMatrix = [
            [0, 0],
            [0, 2],
        ];

        self::assertEquals($confusionMatrix, ConfusionMatrix::compute($actualLabels, $predictedLabels, $labels));
    }
}
