<?php

declare(strict_types=1);

namespace Phpml\Math\Statistic;

use Phpml\Exception\InvalidArgumentException;

class Covariance
{
    /**
     * Calculates covariance from two given arrays, x and y, respectively.
     *
     * @throws InvalidArgumentException
     */
    public static function fromXYArrays(array $x, array $y, bool $sample = true, ?float $meanX = null, ?float $meanY = null): float
    {
        $n = count($x);
        if (0 === $n || 0 === count($y)) {
            throw new InvalidArgumentException('The array has zero elements');
        }

        if ($sample && 1 === $n) {
            throw new InvalidArgumentException('The array must have at least 2 elements');
        }

        if (null === $meanX) {
            $meanX = Mean::arithmetic($x);
        }

        if (null === $meanY) {
            $meanY = Mean::arithmetic($y);
        }

        $sum = 0.0;
        foreach ($x as $index => $xi) {
            $yi = $y[$index];
            $sum += ($xi - $meanX) * ($yi - $meanY);
        }

        if ($sample) {
            --$n;
        }

        return $sum / $n;
    }

    /**
     * Calculates covariance of two dimensions, i and k in the given data.
     *
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public static function fromDataset(array $data, int $i, int $k, bool $sample = true, ?float $meanX = null, ?float $meanY = null): float
    {
        if (0 === count($data)) {
            throw new InvalidArgumentException('The array has zero elements');
        }

        $n = count($data);
        if ($sample && 1 === $n) {
            throw new InvalidArgumentException('The array must have at least 2 elements');
        }

        if ($i < 0 || $k < 0 || $i >= $n || $k >= $n) {
            throw new InvalidArgumentException('Given indices i and k do not match with the dimensionality of data');
        }

        if (null === $meanX || null === $meanY) {
            $x = array_column($data, $i);
            $y = array_column($data, $k);

            $meanX = Mean::arithmetic($x);
            $meanY = Mean::arithmetic($y);
            $sum = 0.0;
            foreach ($x as $index => $xi) {
                $yi = $y[$index];
                $sum += ($xi - $meanX) * ($yi - $meanY);
            }
        } else {
            // In the case, whole dataset given along with dimension indices, i and k,
            // we would like to avoid getting column data with array_column and operate
            // over this extra copy of column data for memory efficiency purposes.
            //
            // Instead we traverse through the whole data and get what we actually need
            // without copying the data. This way, memory use will be reduced
            // with a slight cost of CPU utilization.
            $sum = 0.0;
            foreach ($data as $row) {
                $val = [0, 0];
                foreach ($row as $index => $col) {
                    if ($index == $i) {
                        $val[0] = $col - $meanX;
                    }

                    if ($index == $k) {
                        $val[1] = $col - $meanY;
                    }
                }

                $sum += $val[0] * $val[1];
            }
        }

        if ($sample) {
            --$n;
        }

        return $sum / $n;
    }

    /**
     * Returns the covariance matrix of n-dimensional data.
     */
    public static function covarianceMatrix(array $data, ?array $means = null): array
    {
        $n = count($data[0]);

        if (null === $means) {
            $means = [];
            for ($i = 0; $i < $n; ++$i) {
                $means[] = Mean::arithmetic(array_column($data, $i));
            }
        }

        $cov = [];
        for ($i = 0; $i < $n; ++$i) {
            for ($k = 0; $k < $n; ++$k) {
                if ($i > $k) {
                    $cov[$i][$k] = $cov[$k][$i];
                } else {
                    $cov[$i][$k] = self::fromDataset(
                        $data,
                        $i,
                        $k,
                        true,
                        $means[$i],
                        $means[$k]
                    );
                }
            }
        }

        return $cov;
    }
}
