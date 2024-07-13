<?php

declare(strict_types=1);

namespace Phpml\Math\Distance;

/**
 * Class Chebyshev.
 */
class Chebyshev extends Distance
{
    public function distance(array $a, array $b): float
    {
        return max($this->deltas($a, $b));
    }
}
