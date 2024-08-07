<?php

declare(strict_types=1);

namespace Phpml\Helper\Optimizer;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Exception\InvalidOperationException;

/**
 * Stochastic Gradient Descent optimization method
 * to find a solution for the equation A.ϴ = y where
 *  A (samples) and y (targets) are known and ϴ is unknown.
 */
class StochasticGD extends Optimizer
{
    /**
     * A (samples).
     *
     * @var array
     */
    protected $samples = [];

    /**
     * y (targets).
     *
     * @var array
     */
    protected $targets = [];

    /**
     * Callback function to get the gradient and cost value
     * for a specific set of theta (ϴ) and a pair of sample & target.
     *
     * @var \Closure|null
     */
    protected $gradientCb;

    /**
     * Maximum number of iterations used to train the model.
     *
     * @var int
     */
    protected $maxIterations = 1000;

    /**
     * Learning rate is used to control the speed of the optimization.<br>.
     *
     * Larger values of lr may overshoot the optimum or even cause divergence
     * while small values slows down the convergence and increases the time
     * required for the training
     *
     * @var float
     */
    protected $learningRate = 0.001;

    /**
     * Minimum amount of change in the weights and error values
     * between iterations that needs to be obtained to continue the training.
     *
     * @var float
     */
    protected $threshold = 1e-4;

    /**
     * Enable/Disable early stopping by checking the weight & cost values
     * to see whether they changed large enough to continue the optimization.
     *
     * @var bool
     */
    protected $enableEarlyStop = true;

    /**
     * List of values obtained by evaluating the cost function at each iteration
     * of the algorithm.
     *
     * @var array
     */
    protected $costValues = [];

    /**
     * Initializes the SGD optimizer for the given number of dimensions.
     */
    public function __construct(int $dimensions)
    {
        // Add one more dimension for the bias
        parent::__construct($dimensions + 1);

        $this->dimensions = $dimensions;
    }

    public function setTheta(array $theta): Optimizer
    {
        if (count($theta) !== $this->dimensions + 1) {
            throw new InvalidArgumentException(sprintf('Number of values in the weights array should be %s', $this->dimensions + 1));
        }

        $this->theta = $theta;

        return $this;
    }

    /**
     * Sets minimum value for the change in the theta values
     * between iterations to continue the iterations.<br>.
     *
     * If change in the theta is less than given value then the
     * algorithm will stop training
     *
     * @return $this
     */
    public function setChangeThreshold(float $threshold = 1e-5)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Enable/Disable early stopping by checking at each iteration
     * whether changes in theta or cost value are not large enough.
     *
     * @return $this
     */
    public function setEarlyStop(bool $enable = true)
    {
        $this->enableEarlyStop = $enable;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLearningRate(float $learningRate)
    {
        $this->learningRate = $learningRate;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMaxIterations(int $maxIterations)
    {
        $this->maxIterations = $maxIterations;

        return $this;
    }

    /**
     * Optimization procedure finds the unknow variables for the equation A.ϴ = y
     * for the given samples (A) and targets (y).<br>.
     *
     * The cost function to minimize and the gradient of the function are to be
     * handled by the callback function provided as the third parameter of the method.
     */
    public function runOptimization(array $samples, array $targets, \Closure $gradientCb): array
    {
        $this->samples = $samples;
        $this->targets = $targets;
        $this->gradientCb = $gradientCb;

        $currIter = 0;
        $bestTheta = null;
        $bestScore = 0.0;
        $this->costValues = [];

        while ($this->maxIterations > $currIter++) {
            $theta = $this->theta;

            // Update the guess
            $cost = $this->updateTheta();

            // Save the best theta in the "pocket" so that
            // any future set of theta worse than this will be disregarded
            if (null === $bestTheta || $cost <= $bestScore) {
                $bestTheta = $theta;
                $bestScore = $cost;
            }

            // Add the cost value for this iteration to the list
            $this->costValues[] = $cost;

            // Check for early stop
            if ($this->enableEarlyStop && $this->earlyStop($theta)) {
                break;
            }
        }

        $this->clear();

        // Solution in the pocket is better than or equal to the last state
        // so, we use this solution
        return $this->theta = (array) $bestTheta;
    }

    /**
     * Returns the list of cost values for each iteration executed in
     * last run of the optimization.
     */
    public function getCostValues(): array
    {
        return $this->costValues;
    }

    protected function updateTheta(): float
    {
        $jValue = 0.0;
        $theta = $this->theta;

        if (null === $this->gradientCb) {
            throw new InvalidOperationException('Gradient callback is not defined');
        }

        foreach ($this->samples as $index => $sample) {
            $target = $this->targets[$index];

            $result = ($this->gradientCb)($theta, $sample, $target);

            [$error, $gradient, $penalty] = array_pad($result, 3, 0);

            // Update bias
            $this->theta[0] -= $this->learningRate * $gradient;

            // Update other values
            for ($i = 1; $i <= $this->dimensions; ++$i) {
                $this->theta[$i] -= $this->learningRate *
                    ($gradient * $sample[$i - 1] + $penalty * $this->theta[$i]);
            }

            // Sum error rate
            $jValue += $error;
        }

        return $jValue / count($this->samples);
    }

    /**
     * Checks if the optimization is not effective enough and can be stopped
     * in case large enough changes in the solution do not happen.
     */
    protected function earlyStop(array $oldTheta): bool
    {
        // Check for early stop: No change larger than threshold (default 1e-5)
        $diff = array_map(
            function ($w1, $w2) {
                return abs($w1 - $w2) > $this->threshold ? 1 : 0;
            },
            $oldTheta,
            $this->theta
        );

        if (0 == array_sum($diff)) {
            return true;
        }

        // Check if the last two cost values are almost the same
        $costs = array_slice($this->costValues, -2);
        if (2 === count($costs) && abs($costs[1] - $costs[0]) < $this->threshold) {
            return true;
        }

        return false;
    }

    /**
     * Clears the optimizer internal vars after the optimization process.
     */
    protected function clear(): void
    {
        $this->samples = [];
        $this->targets = [];
        $this->gradientCb = null;
    }
}
