<?php

declare(strict_types=1);

namespace Phpml\Tests\Classification;

use Phpml\Classification\DecisionTree;
use Phpml\ModelManager;
use PHPUnit\Framework\TestCase;

class DecisionTreeTest extends TestCase
{
    /**
     * @var array
     */
    private $data = [
        ['sunny',       85,    85,    'false',    'Dont_play'],
        ['sunny',       80,    90,    'true',     'Dont_play'],
        ['overcast',    83,    78,    'false',    'Play'],
        ['rain',        70,    96,    'false',    'Play'],
        ['rain',        68,    80,    'false',    'Play'],
        ['rain',        65,    70,    'true',     'Dont_play'],
        ['overcast',    64,    65,    'true',     'Play'],
        ['sunny',       72,    95,    'false',    'Dont_play'],
        ['sunny',       69,    70,    'false',    'Play'],
        ['rain',        75,    80,    'false',    'Play'],
        ['sunny',       75,    70,    'true',     'Play'],
        ['overcast',    72,    90,    'true',     'Play'],
        ['overcast',    81,    75,    'false',    'Play'],
        ['rain',        71,    80,    'true',     'Dont_play'],
    ];

    /**
     * @var array
     */
    private $extraData = [
        ['scorching',   90,     95,     'false',   'Dont_play'],
        ['scorching',  100,     93,     'true',    'Dont_play'],
    ];

    public function testPredictSingleSample(): void
    {
        [$data, $targets] = $this->getData($this->data);
        $classifier = new DecisionTree(5);
        $classifier->train($data, $targets);
        self::assertEquals('Dont_play', $classifier->predict(['sunny', 78, 72, 'false']));
        self::assertEquals('Play', $classifier->predict(['overcast', 60, 60, 'false']));
        self::assertEquals('Dont_play', $classifier->predict(['rain', 60, 60, 'true']));

        [$data, $targets] = $this->getData($this->extraData);
        $classifier->train($data, $targets);
        self::assertEquals('Dont_play', $classifier->predict(['scorching', 95, 90, 'true']));
        self::assertEquals('Play', $classifier->predict(['overcast', 60, 60, 'false']));
    }

    public function testSaveAndRestore(): void
    {
        [$data, $targets] = $this->getData($this->data);
        $classifier = new DecisionTree(5);
        $classifier->train($data, $targets);

        $testSamples = [['sunny', 78, 72, 'false'], ['overcast', 60, 60, 'false']];
        $predicted = $classifier->predict($testSamples);

        $filename = 'decision-tree-test-'.random_int(100, 999).'-'.uniqid('', false);
        $filepath = (string) tempnam(sys_get_temp_dir(), $filename);
        $modelManager = new ModelManager();
        $modelManager->saveToFile($classifier, $filepath);

        $restoredClassifier = $modelManager->restoreFromFile($filepath);
        self::assertEquals($classifier, $restoredClassifier);
        self::assertEquals($predicted, $restoredClassifier->predict($testSamples));
    }

    public function testTreeDepth(): void
    {
        [$data, $targets] = $this->getData($this->data);
        $classifier = new DecisionTree(5);
        $classifier->train($data, $targets);
        self::assertTrue($classifier->actualDepth <= 5);
    }

    private function getData(array $input): array
    {
        $targets = array_column($input, 4);
        array_walk($input, function (&$v): void {
            array_splice($v, 4, 1);
        });

        return [$input, $targets];
    }
}
