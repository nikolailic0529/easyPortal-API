<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use Exception;
use GraphQL\Language\AST\StringValueNode;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Scalars\HtmlString
 */
class HtmlStringTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderHtml
     */
    public function testSerialize(?string $expected, string $value): void {
        $scalar = new HtmlString();
        $actual = $scalar->serialize($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderHtml
     */
    public function testParseValue(string|Exception $expected, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new HtmlString();
        $actual = $scalar->parseValue($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderHtml
     */
    public function testParseLiteral(string|Exception $expected, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $node   = new StringValueNode(['value' => $value]);
        $scalar = new HtmlString();
        $actual = $scalar->parseLiteral($node);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, string}>
     */
    public function dataProviderHtml(): array {
        return [
            'string'       => [
                'string without html',
                'string without html',
            ],
            'allowed html' => [
                <<<'HTML'
                <p class="ql-align-center">text <strong>strong</strong></p>
                <p class="ql-align-right">text <em>em</em></p>
                <p class="ql-align-justify">text <u>u</u></p>
                <p class="ql-indent-1">text <s>s</s></p>
                <br><ul><li class="ql-indent-2">one</li>
                    <li>two</li>
                </ul><ol><li class="ql-align-right">one</li>
                    <li>two</li>
                </ol>
                HTML,
                <<<'HTML'
                <p class="ql-align-center">text <strong>strong</strong></p>
                <p class="ql-align-right">text <em>em</em></p>
                <p class="ql-align-justify">text <u>u</u></p>
                <p class="ql-indent-1">text <s>s</s></p>
                <br><ul><li class="ql-indent-2">one</li>
                    <li>two</li>
                </ul><ol><li class="ql-align-right">one</li>
                    <li>two</li>
                </ol>
                HTML,
            ],
            'invalid html' => [
                <<<'HTML'
                <p>text <strong>strong</strong></p>
                <p class="ql-indent-7">span</p>
                HTML,
                <<<'HTML'
                <p class="not allowed">text <strong>strong</strong></p>
                <p class="ql-indent-7"><span>span</span></p>
                HTML,
            ],
        ];
    }
    // </editor-fold>
}
