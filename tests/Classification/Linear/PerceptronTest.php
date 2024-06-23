<?php

declare(strict_types=1);

namespace Phpml\Tests\Classification\Linear;

use Phpml\Classification\Linear\Perceptron;
use Phpml\Exception\InvalidArgumentException;
use Phpml\ModelManager;
use PHPUnit\Framework\TestCase;

class PerceptronTest extends TestCase
{
    public function testPerceptronThrowWhenLearningRateOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Learning rate should be a float value between 0.0(exclusive) and 1.0(inclusive)');
        new Perceptron(0, 5000);
    }

    public function testPerceptronThrowWhenMaxIterationsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum number of iterations must be an integer greater than 0');
        new Perceptron(0.001, 0);
    }

    public function testPredictSingleSample(): void
    {
        // AND problem
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1], [0.6, 0.6]];
        $targets = [0, 0, 0, 1, 1];
        $classifier = new Perceptron(0.001, 5000);
        $classifier->setEarlyStop(false);
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0.1, 0.2]));
        self::assertEquals(0, $classifier->predict([0, 1]));
        self::assertEquals(1, $classifier->predict([1.1, 0.8]));

        // OR problem
        $samples = [[0.1, 0.1], [0.4, 0.], [0., 0.3], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 0, 0, 1, 1, 1];
        $classifier = new Perceptron(0.001, 5000, false);
        $classifier->setEarlyStop(false);
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0., 0.]));
        self::assertEquals(1, $classifier->predict([0.1, 0.99]));
        self::assertEquals(1, $classifier->predict([1.1, 0.8]));

        // By use of One-v-Rest, Perceptron can perform multi-class classification
        // The samples should be separable by lines perpendicular to the dimensions
        $samples = [
            [0, 0], [0, 1], [1, 0], [1, 1], // First group : a cluster at bottom-left corner in 2D
            [5, 5], [6, 5], [5, 6], [7, 5], // Second group: another cluster at the middle-right
            [3, 10], [3, 10], [3, 8], [3, 9],  // Third group : cluster at the top-middle
        ];
        $targets = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2];

        $classifier = new Perceptron();
        $classifier->setEarlyStop(false);
        $classifier->train($samples, $targets);
        self::assertEquals(0, $classifier->predict([0.5, 0.5]));
        self::assertEquals(1, $classifier->predict([6.0, 5.0]));
        self::assertEquals(2, $classifier->predict([3.0, 9.5]));

        // Extra partial training should lead to the same results.
        $classifier->partialTrain([[0, 1], [1, 0]], [0, 0], [0, 1, 2]);
        self::assertEquals(0, $classifier->predict([0.5, 0.5]));
        self::assertEquals(1, $classifier->predict([6.0, 5.0]));
        self::assertEquals(2, $classifier->predict([3.0, 9.5]));

        // Train should clear previous data.
        $samples = [
            [0, 0], [0, 1], [1, 0], [1, 1], // First group : a cluster at bottom-left corner in 2D
            [5, 5], [6, 5], [5, 6], [7, 5], // Second group: another cluster at the middle-right
            [3, 10], [3, 10], [3, 8], [3, 9],  // Third group : cluster at the top-middle
        ];
        $targets = [2, 2, 2, 2, 0, 0, 0, 0, 1, 1, 1, 1];
        $classifier->train($samples, $targets);
        self::assertEquals(2, $classifier->predict([0.5, 0.5]));
        self::assertEquals(0, $classifier->predict([6.0, 5.0]));
        self::assertEquals(1, $classifier->predict([3.0, 9.5]));
    }

    public function testSaveAndRestore(): void
    {
        // Instantinate new Percetron trained for OR problem
        $samples = [[0, 0], [1, 0], [0, 1], [1, 1]];
        $targets = [0, 1, 1, 1];
        $classifier = new Perceptron();
        $classifier->train($samples, $targets);
        $testSamples = [[0, 1], [1, 1], [0.2, 0.1]];
        $predicted = $classifier->predict($testSamples);

        $filename = 'perceptron-test-'.random_int(100, 999).'-'.uniqid('', false);
        $filepath = (string) tempnam(sys_get_temp_dir(), $filename);
        $modelManager = new ModelManager();
        $modelManager->saveToFile($classifier, $filepath);

        $restoredClassifier = $modelManager->restoreFromFile($filepath);
        self::assertEquals($classifier, $restoredClassifier);
        self::assertEquals($predicted, $restoredClassifier->predict($testSamples));
    }
}
