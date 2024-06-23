<?php

declare(strict_types=1);

namespace Phpml\Tests\Dataset;

use Phpml\Dataset\CsvDataset;
use Phpml\Exception\FileException;
use PHPUnit\Framework\TestCase;

class CsvDatasetTest extends TestCase
{
    public function testThrowExceptionOnMissingFile(): void
    {
        $this->expectException(FileException::class);
        new CsvDataset('missingFile', 3);
    }

    public function testSampleCsvDatasetWithHeaderRow(): void
    {
        $filePath = dirname(__FILE__).'/Resources/dataset.csv';

        $dataset = new CsvDataset($filePath, 2, true);

        self::assertCount(10, $dataset->getSamples());
        self::assertCount(10, $dataset->getTargets());
    }

    public function testSampleCsvDatasetWithoutHeaderRow(): void
    {
        $filePath = dirname(__FILE__).'/Resources/dataset.csv';

        $dataset = new CsvDataset($filePath, 2, false);

        self::assertCount(11, $dataset->getSamples());
        self::assertCount(11, $dataset->getTargets());
    }

    public function testLongCsvDataset(): void
    {
        $filePath = dirname(__FILE__).'/Resources/longdataset.csv';

        $dataset = new CsvDataset($filePath, 1000, false);

        self::assertCount(1000, $dataset->getSamples()[0]);
        self::assertEquals('label', $dataset->getTargets()[0]);
    }
}
