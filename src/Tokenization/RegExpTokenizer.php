<?php

namespace Phpml\Tokenization;

/**
 * Based in Sam Hocevar <sam@hocevar.net> work,
 * see https://github.com/angeloskath/php-nlp-tools
 */
class RegExpTokenizer implements Tokenizer
{
    private array $regExpPatterns;

    public function __construct(?array $regExpPatterns = null)
    {
        if (null === $regExpPatterns) {
            $this->regExpPatterns = [
                ["/\s+/"," "],              // replace many spaces with one
                ["/'(m|ve|d|s)/", " '\$1"], // split I've, it's, we've, we'd...
                "/\W/u"                     // split on every non-alphanumeric
            ];
        } else {
            $this->regExpPatterns = $regExpPatterns;
        }
    }

    public function tokenize(string $text): array
    {
        $text = array($text);
        foreach ($this->regExpPatterns as $regExpPattern) {
            if (!is_array($regExpPattern)) {
                $regExpPattern = [$regExpPattern];
            }

            if (count($regExpPattern) == 1) { // split pattern
                $this->split($text, $regExpPattern[0]);
            } elseif (is_int($regExpPattern[1])) { // match pattern
                $this->match($text, $regExpPattern[0], $regExpPattern[1]);
            } else { // replace pattern
                $this->replace($text, $regExpPattern[0], $regExpPattern[1]);
            }
        }

        return $text;
    }

    protected function split(array &$str, $pattern): void
    {
        $tokens = array();
        foreach ($str as $s) {
            $tokens = array_merge(
                $tokens,
                preg_split($pattern, $s, 0, PREG_SPLIT_NO_EMPTY)
            );
        }

        $str = $tokens;
    }

    protected function match(array &$str, $pattern, $keep): void
    {
        $tokens = array();
        foreach ($str as $s) {
            preg_match_all($pattern, $s, $m);
            $tokens = array_merge(
                $tokens,
                $m[$keep]
            );
        }

        $str = $tokens;
    }

    protected function replace(array &$str, $pattern, $replacement): void
    {
        foreach ($str as &$s) {
            $s = preg_replace($pattern, $replacement, $s);
        }
    }
}
