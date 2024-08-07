<?php

declare(strict_types=1);

namespace Phpml\DimensionReduction;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Exception\InvalidOperationException;
use Phpml\Math\Statistic\Covariance;
use Phpml\Math\Statistic\Mean;

class PCA extends EigenTransformerBase
{
    /**
     * Temporary storage for mean values for each dimension in given data.
     *
     * @var array
     */
    protected $means = [];

    /**
     * @var bool
     */
    protected $fit = false;

    /**
     * PCA (Principal Component Analysis) used to explain given
     * data with lower number of dimensions. This analysis transforms the
     * data to a lower dimensional version of it by conserving a proportion of total variance
     * within the data. It is a lossy data compression technique.<br>.
     *
     * @param float $totalVariance Total explained variance to be preserved
     * @param int   $numFeatures   Number of features to be preserved
     *
     * @throws InvalidArgumentException
     */
    public function __construct(?float $totalVariance = null, ?int $numFeatures = null)
    {
        if (null !== $totalVariance && ($totalVariance < 0.1 || $totalVariance > 0.99)) {
            throw new InvalidArgumentException('Total variance can be a value between 0.1 and 0.99');
        }

        if (null !== $numFeatures && $numFeatures <= 0) {
            throw new InvalidArgumentException('Number of features to be preserved should be greater than 0');
        }

        if ((null !== $totalVariance) === (null !== $numFeatures)) {
            throw new InvalidArgumentException('Either totalVariance or numFeatures should be specified in order to run the algorithm');
        }

        if (null !== $numFeatures) {
            $this->numFeatures = $numFeatures;
        }

        if (null !== $totalVariance) {
            $this->totalVariance = $totalVariance;
        }
    }

    /**
     * Takes a data and returns a lower dimensional version
     * of this data while preserving $totalVariance or $numFeatures. <br>
     * $data is an n-by-m matrix and returned array is
     * n-by-k matrix where k <= m.
     */
    public function fit(array $data): array
    {
        $n = count($data[0]);

        $data = $this->normalize($data, $n);

        $covMatrix = Covariance::covarianceMatrix($data, array_fill(0, $n, 0));

        $this->eigenDecomposition($covMatrix);

        $this->fit = true;

        return $this->reduce($data);
    }

    /**
     * Transforms the given sample to a lower dimensional vector by using
     * the eigenVectors obtained in the last run of <code>fit</code>.
     *
     * @throws InvalidOperationException
     */
    public function transform(array $sample): array
    {
        if (!$this->fit) {
            throw new InvalidOperationException('PCA has not been fitted with respect to original dataset, please run PCA::fit() first');
        }

        if (!is_array($sample[0])) {
            $sample = [$sample];
        }

        $sample = $this->normalize($sample, count($sample[0]));

        return $this->reduce($sample);
    }

    protected function calculateMeans(array $data, int $n): void
    {
        // Calculate means for each dimension
        $this->means = [];
        for ($i = 0; $i < $n; ++$i) {
            $column = array_column($data, $i);
            $this->means[] = Mean::arithmetic($column);
        }
    }

    /**
     * Normalization of the data includes subtracting mean from
     * each dimension therefore dimensions will be centered to zero.
     */
    protected function normalize(array $data, int $n): array
    {
        if (0 === count($this->means)) {
            $this->calculateMeans($data, $n);
        }

        // Normalize data
        foreach (array_keys($data) as $i) {
            for ($k = 0; $k < $n; ++$k) {
                $data[$i][$k] -= $this->means[$k];
            }
        }

        return $data;
    }
}
