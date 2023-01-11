<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use DateTimeInterface;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use Illuminate\Support\Facades\Date as DateFacade;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Scalars\Date
 */
class DateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(string $expected, string $value): void {
        $value  = DateFacade::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $value);
        $scalar = new Date();
        $actual = $scalar->serialize($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderParse
     */
    public function testParseValue(string|Exception $expected, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $scalar = new Date();
        $actual = $scalar->parseValue($value);

        self::assertEquals($expected, $actual ? $actual->format(DateTimeInterface::RFC3339_EXTENDED) : null);
    }

    /**
     * @dataProvider dataProviderParse
     */
    public function testParseLiteral(string|Exception $expected, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $node   = new StringValueNode(['value' => $value]);
        $scalar = new Date();
        $actual = $scalar->parseLiteral($node);

        self::assertEquals($expected, $actual ? $actual->format(DateTimeInterface::RFC3339_EXTENDED) : null);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, string}>
     */
    public function dataProviderSerialize(): array {
        return [
            'valid date'   => ['2102-12-01', '2102-12-01T00:00:00.000+00:00'],
            'invalid date' => ['0002-12-01', '0002-12-01T00:00:00.000+00:00'],
        ];
    }

    /**
     * @return array<string, array{string|Exception, string}>
     */
    public function dataProviderParse(): array {
        return [
            'valid date'   => ['2102-12-01T00:00:00.000+00:00', '2102-12-01'],
            'invalid date' => ['0002-12-01T00:00:00.000+00:00', '02-12-01'],
            'datetime'     => [new Error('Trailing data'), '2102-12-01T00:00:00'],
        ];
    }
    // </editor-fold>
}
