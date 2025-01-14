<?php

namespace Phpml\Tests\Clustering;

use Phpml\Clustering\XMeans;
use Phpml\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class XMeansTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testXMeansSamplesClustering(): void
    {
        $samples = [[1, 1], [8, 7], [1, 2], [7, 8], [2, 1], [8, 9]];

        $minClusters = 2;
        $maxClusters = 5;

        $xMeans = new XMeans($minClusters, $maxClusters);
        $clusters = $xMeans->cluster($samples);

        self::assertGreaterThanOrEqual($minClusters, count($clusters));
        self::assertLessThanOrEqual($maxClusters, count($clusters));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testXMeansSamplesLabeledClustering(): void
    {
        $samples = [
            '555' => [1, 1],
            '666' => [8, 7],
            'ABC' => [1, 2],
            [8, 9],
            'DEF' => [7, 8],
            668 => [2, 1],
        ];

        $minClusters = 2;
        $maxClusters = 5;

        $xMeans = new XMeans($minClusters, $maxClusters);
        $clusters = $xMeans->cluster($samples);

        self::assertGreaterThanOrEqual($minClusters, count($clusters));
        self::assertLessThanOrEqual($maxClusters, count($clusters));
    }
}
