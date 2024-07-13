<?php

declare(strict_types=1);

namespace Phpml\Preprocessing\Imputer;

interface Strategy
{
    public function replaceValue(array $currentAxis);
}
