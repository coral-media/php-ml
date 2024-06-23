<?php

declare(strict_types=1);

namespace Phpml\Tests\Clustering;

use Phpml\Clustering\KMeans;
use Phpml\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class KMeansTest extends TestCase
{
    public function testKMeansSamplesClustering(): void
    {
        $samples = [[1, 1], [8, 7], [1, 2], [7, 8], [2, 1], [8, 9]];

        $kmeans = new KMeans(2);
        $clusters = $kmeans->cluster($samples);

        self::assertCount(2, $clusters);

        foreach ($samples as $index => $sample) {
            if (in_array($sample, $clusters[0], true) || in_array($sample, $clusters[1], true)) {
                unset($samples[$index]);
            }
        }

        self::assertCount(0, $samples);
    }

    public function testKMeansSamplesLabeledClustering(): void
    {
        $samples = [
            '555' => [1, 1],
            '666' => [8, 7],
            'ABC' => [1, 2],
            'DEF' => [7, 8],
            668 => [2, 1],
            [8, 9],
        ];

        $kmeans = new KMeans(2);
        $clusters = $kmeans->cluster($samples);

        self::assertCount(2, $clusters);

        foreach ($samples as $index => $sample) {
            if (in_array($sample, $clusters[0], true) || in_array($sample, $clusters[1], true)) {
                self::assertArrayHasKey($index, $clusters[0] + $clusters[1]);
                unset($samples[$index]);
            }
        }

        self::assertCount(0, $samples);
    }

    public function testKMeansInitializationMethods(): void
    {
        $samples = [
            [180, 155], [186, 159], [119, 185], [141, 147], [157, 158],
            [176, 122], [194, 160], [113, 193], [190, 148], [152, 154],
            [162, 146], [188, 144], [185, 124], [163, 114], [151, 140],
            [175, 131], [186, 162], [181, 195], [147, 122], [143, 195],
            [171, 119], [117, 165], [169, 121], [159, 160], [159, 112],
            [115, 122], [149, 193], [156, 135], [118, 120], [139, 159],
            [150, 115], [181, 136], [167, 162], [132, 115], [175, 165],
            [110, 147], [175, 118], [113, 145], [130, 162], [195, 179],
            [164, 111], [192, 114], [194, 149], [139, 113], [160, 168],
            [162, 110], [174, 144], [137, 142], [197, 160], [147, 173],
        ];

        $kmeans = new KMeans(4, KMeans::INIT_KMEANS_PLUS_PLUS);
        $clusters = $kmeans->cluster($samples);
        self::assertCount(4, $clusters);

        $kmeans = new KMeans(4, KMeans::INIT_RANDOM);
        $clusters = $kmeans->cluster($samples);
        self::assertCount(4, $clusters);
    }

    public function testThrowExceptionOnInvalidClusterNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new KMeans(0);
    }
}
