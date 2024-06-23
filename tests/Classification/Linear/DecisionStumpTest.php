<?php

declare(strict_types=1);

namespace Phpml\Tests\Classification\Linear;

use Phpml\Classification\Linear\DecisionStump;
use Phpml\Exception\InvalidArgumentException;
use Phpml\ModelManager;
use PHPUnit\Framework\TestCase;

class DecisionStumpTest extends TestCase
{
    public function testTrainThrowWhenSample(): void
    {
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 0, 1, 1];

        $classifier = new DecisionStump();
        $classifier->setSampleWeights([0.1, 0.1, 0.1]);

        $this->expectException(InvalidArgumentException::class);
        $classifier->train($samples, $targets);
    }

    public function testPredictSingleSample(): void
    {
        // Samples should be separable with a line perpendicular
        // to any dimension given in the dataset
        //
        // First: horizontal test
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 0, 1, 1];
        $classifier = new DecisionStump();
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0.1, 0.2]));
        self::assertEquals(0, $classifier->predict([1.1, 0.2]));
        self::assertEquals(1, $classifier->predict([0.1, 0.99]));
        self::assertEquals(1, $classifier->predict([1.1, 0.8]));

        // Then: vertical test
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 1, 0, 1];
        $classifier = new DecisionStump();
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0.1, 0.2]));
        self::assertEquals(0, $classifier->predict([0.1, 1.1]));
        self::assertEquals(1, $classifier->predict([1.0, 0.99]));
        self::assertEquals(1, $classifier->predict([1.1, 0.1]));

        // By use of One-v-Rest, DecisionStump can perform multi-class classification
        // The samples should be separable by lines perpendicular to the dimensions
        $samples = [
            [0, 0], [0, 1], [1, 0], [1, 1], // First group : a cluster at bottom-left corner in 2D
            [5, 5], [6, 5], [5, 6], [7, 5], // Second group: another cluster at the middle-right
            [3, 10], [3, 10], [3, 8], [3, 9],  // Third group : cluster at the top-middle
        ];
        $targets = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2];

        $classifier = new DecisionStump();
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0.5, 0.5]));
        self::assertEquals(1, $classifier->predict([6.0, 5.0]));
        self::assertEquals(2, $classifier->predict([2.9, 9.5]));
    }

    public function testSaveAndRestore(): void
    {
        // Instantinate new Percetron trained for OR problem
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 1, 1, 1];
        $classifier = new DecisionStump();
        $classifier->train($samples, $targets);
        $testSamples = [[0, 1], [1, 1], [0.2, 0.1]];
        $predicted = $classifier->predict($testSamples);

        $filename = 'dstump-test-'.random_int(100, 999).'-'.uniqid('', false);
        $filepath = (string) tempnam(sys_get_temp_dir(), $filename);
        $modelManager = new ModelManager();
        $modelManager->saveToFile($classifier, $filepath);

        $restoredClassifier = $modelManager->restoreFromFile($filepath);
        self::assertEquals($classifier, $restoredClassifier);
        self::assertEquals($predicted, $restoredClassifier->predict($testSamples));
    }
}
