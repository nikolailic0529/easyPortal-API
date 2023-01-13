<?php declare(strict_types = 1);

namespace App\Rules;

use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\DateTime
 */
class DateTimeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $rule   = $this->app->make(DateTime::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.date_format' => 'message validation.date_format :format',
                ],
            ];
        });

        self::assertEquals(
            'message validation.date_format Y-m-d\TH:i:sP',
            (new DateTime())->message(),
        );
    }

    /**
     * @dataProvider dataProviderParse
     */
    public function testParse(Exception|string|null $expected, ?string $tz, ?string $value): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $this->setSettings([
            'app.timezone' => $tz,
        ]);

        self::assertEquals(
            $expected,
            (new DateTime())->parse($value)?->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'valid datetime'                => [true, '2102-12-01T00:00:00+00:00'],
            'valid datetime with time zone' => [true, '0002-12-01T00:00:00+03:00'],
            'invalid datetime'              => [false, '2102-12-01T00:00:00.000+00:00'],
            'not a datetime'                => [false, 'sdfsdf'],
            'date'                          => [false, '2102-12-01'],
            'empty string'                  => [false, ''],
        ];
    }

    /**
     * @return array<string, array{Exception|string|null, string|null, string|null}>
     */
    public function dataProviderParse(): array {
        return [
            'date'                          => [
                new InvalidFormatException('Data missing'),
                null,
                '2102-12-01',
            ],
            'invalid date'                  => [
                new InvalidFormatException('Data missing'),
                null,
                '02-12-01',
            ],
            'datetime without timezone'     => [
                new InvalidFormatException('Data missing'),
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
            'null'                          => [
                null,
                null,
                null,
            ],
        ];
    }
    // </editor-fold>
}
