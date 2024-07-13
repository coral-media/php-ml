<?php

declare(strict_types=1);

namespace Phpml\FeatureSelection;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Exception\InvalidOperationException;
use Phpml\FeatureSelection\ScoringFunction\ANOVAFValue;
use Phpml\Transformer;

final class SelectKBest implements Transformer
{
    /**
     * @var ScoringFunction
     */
    private $scoringFunction;

    /**
     * @var int
     */
    private $k;

    /**
     * @var array|null
     */
    private $scores;

    /**
     * @var array|null
     */
    private $keepColumns;

    public function __construct(int $k = 10, ?ScoringFunction $scoringFunction = null)
    {
        if (null === $scoringFunction) {
            $scoringFunction = new ANOVAFValue();
        }

        $this->scoringFunction = $scoringFunction;
        $this->k = $k;
    }

    public function fit(array $samples, ?array $targets = null): void
    {
        if (null === $targets || 0 === count($targets)) {
            throw new InvalidArgumentException('The array has zero elements');
        }

        $this->scores = $sorted = $this->scoringFunction->score($samples, $targets);
        if ($this->k >= count($sorted)) {
            return;
        }

        arsort($sorted);
        $this->keepColumns = array_slice($sorted, 0, $this->k, true);
    }

    public function transform(array &$samples, ?array &$targets = null): void
    {
        if (null === $this->keepColumns) {
            return;
        }

        foreach ($samples as &$sample) {
            $sample = array_values(array_intersect_key($sample, $this->keepColumns));
        }
    }

    public function scores(): array
    {
        if (null === $this->scores) {
            throw new InvalidOperationException('SelectKBest require to fit first to get scores');
        }

        return $this->scores;
    }
}
