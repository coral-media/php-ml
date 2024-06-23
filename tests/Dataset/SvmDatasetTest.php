<?php

declare(strict_types=1);

namespace Phpml\Tests\Dataset;

use Phpml\Dataset\SvmDataset;
use Phpml\Exception\DatasetException;
use Phpml\Exception\FileException;
use PHPUnit\Framework\TestCase;

class SvmDatasetTest extends TestCase
{
    public function testSvmDatasetEmpty(): void
    {
        $filePath = self::getFilePath('empty.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
        ];
        $expectedTargets = [
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDataset1x1(): void
    {
        $filePath = self::getFilePath('1x1.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [2.3],
        ];
        $expectedTargets = [
            0,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDataset3x1(): void
    {
        $filePath = self::getFilePath('3x1.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [2.3],
            [4.56],
            [78.9],
        ];
        $expectedTargets = [
            1,
            0,
            1,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDataset3x4(): void
    {
        $filePath = self::getFilePath('3x4.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [2, 4, 6, 8],
            [3, 5, 7, 9],
            [1.2, 3.4, 5.6, 7.8],
        ];
        $expectedTargets = [
            1,
            2,
            0,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDatasetSparse(): void
    {
        $filePath = self::getFilePath('sparse.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [0, 3.45, 0, 0, 0],
            [0, 0, 0, 0, 6.789],
        ];
        $expectedTargets = [
            0,
            1,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDatasetComments(): void
    {
        $filePath = self::getFilePath('comments.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [2],
            [34],
        ];
        $expectedTargets = [
            0,
            1,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDatasetTabs(): void
    {
        $filePath = self::getFilePath('tabs.svm');
        $dataset = new SvmDataset($filePath);

        $expectedSamples = [
            [23, 45],
        ];
        $expectedTargets = [
            1,
        ];

        self::assertEquals($expectedSamples, $dataset->getSamples());
        self::assertEquals($expectedTargets, $dataset->getTargets());
    }

    public function testSvmDatasetMissingFile(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('File "err_file_not_exists.svm" missing.');

        new SvmDataset(self::getFilePath('err_file_not_exists.svm'));
    }

    public function testSvmDatasetEmptyLine(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid target "".');

        new SvmDataset(self::getFilePath('err_empty_line.svm'));
    }

    public function testSvmDatasetNoLabels(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid target "1:2.3".');

        new SvmDataset(self::getFilePath('err_no_labels.svm'));
    }

    public function testSvmDatasetStringLabels(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid target "A".');

        new SvmDataset(self::getFilePath('err_string_labels.svm'));
    }

    public function testSvmDatasetInvalidSpaces(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid target "".');

        new SvmDataset(self::getFilePath('err_invalid_spaces.svm'));
    }

    public function testSvmDatasetStringIndex(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid index "x".');

        new SvmDataset(self::getFilePath('err_string_index.svm'));
    }

    public function testSvmDatasetIndexZero(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid index "0".');

        new SvmDataset(self::getFilePath('err_index_zero.svm'));
    }

    public function testSvmDatasetInvalidValue(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid value "xyz".');

        new SvmDataset(self::getFilePath('err_invalid_value.svm'));
    }

    public function testSvmDatasetInvalidFeature(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Invalid value "12345".');

        new SvmDataset(self::getFilePath('err_invalid_feature.svm'));
    }

    private static function getFilePath(string $baseName): string
    {
        return __DIR__.'/Resources/svm/'.$baseName;
    }
}
