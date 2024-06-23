<?php

declare(strict_types=1);

namespace Phpml\Tests\Math\Statistic;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Math\Statistic\Correlation;
use PHPUnit\Framework\TestCase;

class CorrelationTest extends TestCase
{
    public function testPearsonCorrelation(): void
    {
        //http://www.stat.wmich.edu/s216/book/node126.html
        $delta = 0.001;
        $x = [9300,  10565,  15000,  15000,  17764,  57000,  65940,  73676,  77006,  93739, 146088, 153260];
        $y = [7100, 15500, 4400, 4400, 5900, 4600, 8800, 2000, 2750, 2550,  960, 1025];
        self::assertEqualsWithDelta(-0.641, Correlation::pearson($x, $y), $delta);

        //http://www.statisticshowto.com/how-to-compute-pearsons-correlation-coefficients/
        $delta = 0.001;
        $x = [43, 21, 25, 42, 57, 59];
        $y = [99, 65, 79, 75, 87, 82];
        self::assertEqualsWithDelta(0.549, Correlation::pearson($x, $y), $delta);

        $delta = 0.001;
        $x = [60, 61, 62, 63, 65];
        $y = [3.1, 3.6, 3.8, 4, 4.1];
        self::assertEqualsWithDelta(0.911, Correlation::pearson($x, $y), $delta);
    }

    public function testThrowExceptionOnInvalidArgumentsForPearsonCorrelation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Correlation::pearson([1, 2, 4], [3, 5]);
    }
}
