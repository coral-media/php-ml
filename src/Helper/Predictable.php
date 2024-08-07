<?php

declare(strict_types=1);

namespace Phpml\Helper;

trait Predictable
{
    public function predict(array $samples)
    {
        if (!is_array($samples[0])) {
            return $this->predictSample($samples);
        }

        $predicted = [];
        foreach ($samples as $index => $sample) {
            $predicted[$index] = $this->predictSample($sample);
        }

        return $predicted;
    }

    abstract protected function predictSample(array $sample);
}
