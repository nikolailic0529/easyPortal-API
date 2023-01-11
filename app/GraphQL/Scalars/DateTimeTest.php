<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use DateTimeInterface;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Date as DateFacade;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Scalars\DateTime
 */
class DateTimeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(?string $expected, string $value): void {
        $value  = DateFacade::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $value);
        $scalar = new DateTime();
        $actual = $scalar->serialize($value);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderParse
     */
    public function testParseValue(string|Exception $expected, ?string $tz, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $this->app->make(Repository::class)->set('app.timezone', $tz);

        $scalar = new DateTime();
        $actual = $scalar->parseValue($value);

        self::assertEquals($expected, $actual ? $actual->format(DateTimeInterface::RFC3339_EXTENDED) : null);
    }

    /**
     * @dataProvider dataProviderParse
     */
    public function testParseLiteral(string|Exception $expected, ?string $tz, string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $this->app->make(Repository::class)->set('app.timezone', $tz);

        $node   = new StringValueNode(['value' => $value]);
        $scalar = new DateTime();
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
            'valid date'           => ['2102-12-01T00:00:00+00:00', '2102-12-01T00:00:00.000+00:00'],
            'invalid date'         => ['0002-12-01T00:00:00+00:00', '0002-12-01T00:00:00.000+00:00'],
            'valid with time zone' => ['0002-12-01T00:00:00+03:00', '0002-12-01T00:00:00.000+03:00'],
        ];
    }

    /**
     * @return array<string, array{string|Error, string|null, string}>
     */
    public function dataProviderParse(): array {
        return [
            'date'                          => [
                new Error('Data missing'),
                null,
                '2102-12-01',
            ],
            'invalid date'                  => [
                new Error('Data missing'),
                null,
                '02-12-01',
            ],
            'datetime without timezone'     => [
                new Error('Data missing'),
                null,
                '2102-12-01T00:00:00',
            ],
            'datetime UTC + UTC'            => [
                '2102-12-01T22:12:01.000+00:00',
                'UTC',
                '2102-12-01T22:12:01+00:00',
            ],
            'datetime Europe/Moscow + UTC'  => [
                '2102-12-02T01:12:01.000+03:00',
                'Europe/Moscow',
                '2102-12-01T22:12:01+00:00',
            ],
            'datetime UTC + Europe/Moscow'  => [
                '2102-12-01T22:12:01.000+00:00',
                'UTC',
                '2102-12-02T01:12:01+03:00',
            ],
            'datetime null + Europe/Moscow' => [
                '2102-12-01T22:12:01.000+00:00',
                null,
                '2102-12-02T01:12:01+03:00',
            ],
        ];
    }
    // </editor-fold>
}
