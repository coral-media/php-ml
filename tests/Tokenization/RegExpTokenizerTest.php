<?php

declare(strict_types=1);

namespace Phpml\Tests\Tokenization;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Tokenization\NGramWordTokenizer;
use Phpml\Tokenization\RegExpTokenizer;

/**
 * Inspiration: https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-ngram-tokenizer.html
 */
class RegExpTokenizerTest extends TokenizerTest
{
    /**
     * @dataProvider textDataProvider
     */
    public function testRegExpTokenization(string $text, array $tokens): void
    {
        $tokenizer = new RegExpTokenizer();
        self::assertEquals($tokens, $tokenizer->tokenize($text));
    }

    public function textDataProvider(): array
    {
        return [
            [
                $this->getSimpleText(),
                [
                    'Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
                    'adipiscing', 'elit', 'Cras', 'consectetur', 'dui',
                    'et', 'lobortis', 'auctor', 'Nulla', 'vitae', 'congue', 'lorem',
                ],
            ],
            [
                $this->getUtf8Text(),
                [
                    '鋍鞎', '鳼', '鞮鞢騉', '袟袘觕', '炟砏', '蒮', '謺貙蹖',
                    '偢偣唲', '蒛', '箷箯緷', '鑴鱱爧', '覮轀', '剆坲', '煘煓瑐',
                    '鬐鶤鶐', '飹勫嫢', '銪', '餀', '枲柊氠', '鍎鞚韕', '焲犈',
                    '殍涾烰', '齞齝囃', '蹅輶', '鄜', '孻憵', '擙樲橚',
                    '藒襓謥', '岯岪弨', '蒮', '廞徲', '孻憵懥', '趡趛踠', '槏',

                ]
            ]
        ];
    }
}
