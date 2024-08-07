<?php

declare(strict_types=1);

namespace Phpml\Classification\Ensemble;

use Phpml\Classification\Classifier;
use Phpml\Classification\Linear\DecisionStump;
use Phpml\Classification\WeightedClassifier;
use Phpml\Exception\InvalidArgumentException;
use Phpml\Helper\Predictable;
use Phpml\Helper\Trainable;
use Phpml\Math\Statistic\Mean;
use Phpml\Math\Statistic\StandardDeviation;

class AdaBoost implements Classifier
{
    use Predictable;
    use Trainable;

    /**
     * Actual labels given in the targets array.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * @var int
     */
    protected $sampleCount;

    /**
     * @var int
     */
    protected $featureCount;

    /**
     * Number of maximum iterations to be done.
     *
     * @var int
     */
    protected $maxIterations;

    /**
     * Sample weights.
     *
     * @var array
     */
    protected $weights = [];

    /**
     * List of selected 'weak' classifiers.
     *
     * @var array
     */
    protected $classifiers = [];

    /**
     * Base classifier weights.
     *
     * @var array
     */
    protected $alpha = [];

    /**
     * @var string
     */
    protected $baseClassifier = DecisionStump::class;

    /**
     * @var array
     */
    protected $classifierOptions = [];

    /**
     * ADAptive BOOSTing (AdaBoost) is an ensemble algorithm to
     * improve classification performance of 'weak' classifiers such as
     * DecisionStump (default base classifier of AdaBoost).
     */
    public function __construct(int $maxIterations = 50)
    {
        $this->maxIterations = $maxIterations;
    }

    /**
     * Sets the base classifier that will be used for boosting (default = DecisionStump).
     */
    public function setBaseClassifier(string $baseClassifier = DecisionStump::class, array $classifierOptions = []): void
    {
        $this->baseClassifier = $baseClassifier;
        $this->classifierOptions = $classifierOptions;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function train(array $samples, array $targets): void
    {
        // Initialize usual variables
        $this->labels = array_keys(array_count_values($targets));
        if (2 !== count($this->labels)) {
            throw new InvalidArgumentException('AdaBoost is a binary classifier and can classify between two classes only');
        }

        // Set all target values to either -1 or 1
        $this->labels = [
            1 => $this->labels[0],
            -1 => $this->labels[1],
        ];
        foreach ($targets as $target) {
            $this->targets[] = $target == $this->labels[1] ? 1 : -1;
        }

        $this->samples = array_merge($this->samples, $samples);
        $this->featureCount = count($samples[0]);
        $this->sampleCount = count($this->samples);

        // Initialize AdaBoost parameters
        $this->weights = array_fill(0, $this->sampleCount, 1.0 / $this->sampleCount);
        $this->classifiers = [];
        $this->alpha = [];

        // Execute the algorithm for a maximum number of iterations
        $currIter = 0;
        while ($this->maxIterations > $currIter++) {
            // Determine the best 'weak' classifier based on current weights
            $classifier = $this->getBestClassifier();
            $errorRate = $this->evaluateClassifier($classifier);

            // Update alpha & weight values at each iteration
            $alpha = $this->calculateAlpha($errorRate);
            $this->updateWeights($classifier, $alpha);

            $this->classifiers[] = $classifier;
            $this->alpha[] = $alpha;
        }
    }

    public function predictSample(array $sample)
    {
        $sum = 0;
        foreach ($this->alpha as $index => $alpha) {
            $h = $this->classifiers[$index]->predict($sample);
            $sum += $h * $alpha;
        }

        return $this->labels[$sum > 0 ? 1 : -1];
    }

    /**
     * Returns the classifier with the lowest error rate with the
     * consideration of current sample weights.
     */
    protected function getBestClassifier(): Classifier
    {
        $ref = new \ReflectionClass($this->baseClassifier);
        /** @var Classifier $classifier */
        $classifier = 0 === count($this->classifierOptions) ? $ref->newInstance() : $ref->newInstanceArgs($this->classifierOptions);

        if ($classifier instanceof WeightedClassifier) {
            $classifier->setSampleWeights($this->weights);
            $classifier->train($this->samples, $this->targets);
        } else {
            [$samples, $targets] = $this->resample();
            $classifier->train($samples, $targets);
        }

        return $classifier;
    }

    /**
     * Resamples the dataset in accordance with the weights and
     * returns the new dataset.
     */
    protected function resample(): array
    {
        $weights = $this->weights;
        $std = StandardDeviation::population($weights);
        $mean = Mean::arithmetic($weights);
        $min = min($weights);
        $minZ = (int) round(($min - $mean) / $std);

        $samples = [];
        $targets = [];
        foreach ($weights as $index => $weight) {
            $z = (int) round(($weight - $mean) / $std) - $minZ + 1;
            for ($i = 0; $i < $z; ++$i) {
                if (0 == random_int(0, 1)) {
                    continue;
                }

                $samples[] = $this->samples[$index];
                $targets[] = $this->targets[$index];
            }
        }

        return [$samples, $targets];
    }

    /**
     * Evaluates the classifier and returns the classification error rate.
     */
    protected function evaluateClassifier(Classifier $classifier): float
    {
        $total = (float) array_sum($this->weights);
        $wrong = 0;
        foreach ($this->samples as $index => $sample) {
            $predicted = $classifier->predict($sample);
            if ($predicted != $this->targets[$index]) {
                $wrong += $this->weights[$index];
            }
        }

        return $wrong / $total;
    }

    /**
     * Calculates alpha of a classifier.
     */
    protected function calculateAlpha(float $errorRate): float
    {
        if (0 == $errorRate) {
            $errorRate = 1e-10;
        }

        return 0.5 * log((1 - $errorRate) / $errorRate);
    }

    /**
     * Updates the sample weights.
     */
    protected function updateWeights(Classifier $classifier, float $alpha): void
    {
        $sumOfWeights = array_sum($this->weights);
        $weightsT1 = [];
        foreach ($this->weights as $index => $weight) {
            $desired = $this->targets[$index];
            $output = $classifier->predict($this->samples[$index]);

            $weight *= exp(-$alpha * $desired * $output) / $sumOfWeights;

            $weightsT1[] = $weight;
        }

        $this->weights = $weightsT1;
    }
}
