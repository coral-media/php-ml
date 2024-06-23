<?php

declare(strict_types=1);

namespace Phpml\Tests\Classification;

use Phpml\Classification\SVC;
use Phpml\ModelManager;
use Phpml\SupportVectorMachine\Kernel;
use PHPUnit\Framework\TestCase;

class SVCTest extends TestCase
{
    public function testPredictSingleSampleWithLinearKernel(): void
    {
        $samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
        $labels = ['a', 'a', 'a', 'b', 'b', 'b'];

        $classifier = new SVC(Kernel::LINEAR, $cost = 1000);
        $classifier->train($samples, $labels);

        self::assertEquals('b', $classifier->predict([3, 2]));
        self::assertEquals('b', $classifier->predict([5, 1]));
        self::assertEquals('b', $classifier->predict([4, 3]));
        self::assertEquals('b', $classifier->predict([4, -5]));

        self::assertEquals('a', $classifier->predict([2, 3]));
        self::assertEquals('a', $classifier->predict([1, 2]));
        self::assertEquals('a', $classifier->predict([1, 5]));
        self::assertEquals('a', $classifier->predict([3, 10]));
    }

    public function testPredictArrayOfSamplesWithLinearKernel(): void
    {
        $trainSamples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
        $trainLabels = ['a', 'a', 'a', 'b', 'b', 'b'];

        $testSamples = [[3, 2], [5, 1], [4, 3], [4, -5], [2, 3], [1, 2], [1, 5], [3, 10]];
        $testLabels = ['b', 'b', 'b', 'b', 'a', 'a', 'a', 'a'];

        $classifier = new SVC(Kernel::LINEAR, $cost = 1000);
        $classifier->train($trainSamples, $trainLabels);
        $predictions = $classifier->predict($testSamples);

        self::assertEquals($testLabels, $predictions);
    }

    public function testSaveAndRestore(): void
    {
        $trainSamples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
        $trainLabels = ['a', 'a', 'a', 'b', 'b', 'b'];

        $testSamples = [[3, 2], [5, 1], [4, 3]];
        $testLabels = ['b', 'b', 'b'];

        $classifier = new SVC(Kernel::LINEAR, $cost = 1000);
        $classifier->train($trainSamples, $trainLabels);
        $predicted = $classifier->predict($testSamples);

        $filepath = (string) tempnam(sys_get_temp_dir(), uniqid('svc-test', true));
        $modelManager = new ModelManager();
        $modelManager->saveToFile($classifier, $filepath);

        $restoredClassifier = $modelManager->restoreFromFile($filepath);
        self::assertEquals($classifier, $restoredClassifier);
        self::assertEquals($predicted, $restoredClassifier->predict($testSamples));
        self::assertEquals($predicted, $testLabels);
    }

    public function testWithNonDotDecimalLocale(): void
    {
        $currentLocale = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'pl_PL.utf8');

        $trainSamples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
        $trainLabels = ['a', 'a', 'a', 'b', 'b', 'b'];

        $testSamples = [[3, 2], [5, 1], [4, 3]];
        $testLabels = ['b', 'b', 'b'];

        $classifier = new SVC(Kernel::LINEAR, $cost = 1000);
        $classifier->train($trainSamples, $trainLabels);

        self::assertEquals($classifier->predict($testSamples), $testLabels);

        setlocale(LC_NUMERIC, (string) $currentLocale);
    }
}
