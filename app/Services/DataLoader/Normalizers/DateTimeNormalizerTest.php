<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use DateTimeInterface;
use Tests\TestCase;

use function config;

/**
 * @internal
 * @covers \App\Services\DataLoader\Normalizers\DateTimeNormalizer
 */
class DateTimeNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, ?string $tz, mixed $value): void {
        config([
            'app.timezone' => $tz,
        ]);

        $actual = DateTimeNormalizer::normalize($value);
        $actual = $actual?->format(DateTimeInterface::RFC3339_EXTENDED);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            'js timestamp + Europe/Moscow' => [
                '2102-12-02T01:12:01.000+03:00',
                'Europe/Moscow',
                4_194_454_321_000,
            ],
            'js timestamp + UTC'           => [
                '2102-12-01T22:12:01.000+00:00',
                'UTC',
                4_194_454_321_000,
            ],
            'js timestamp + null'          => [
                '2102-12-01T22:12:01.000+00:00',
                null,
                4_194_454_321_000,
            ],
            'js timestamp (string) + UTC'  => [
                '2102-12-01T22:12:01.000+00:00',
                'UTC',
                '4194454321000',
            ],
            'Y-m-d string + UTC'           => [
                '2102-12-01T00:00:00.000+00:00',
                'UTC',
                '2102-12-01',
            ],
            'Y-m-d string + null'          => [
                '2102-12-01T00:00:00.000+00:00',
                null,
                '2102-12-01',
            ],
            'd/m/Y string + UTC'           => [
                '2102-12-31T00:00:00.000+00:00',
                'UTC',
                '31/12/2102',
            ],
            'd/m/Y string + null'          => [
                '2102-12-31T00:00:00.000+00:00',
                null,
                '31/12/2102',
            ],
            'empty string'                 => [
                null,
                null,
                '',
            ],
            'null'                         => [
                null,
                null,
                null,
            ],
            'invalid date'                 => [
                null,
                null,
                '2102d',
            ],
            'ISO + Europe/Moscow'          => [
                '2102-12-02T04:12:01.000+03:00',
                'Europe/Moscow',
                '2102-12-02T01:12:01+00:00',
            ],
            'ISO + UTC'                    => [
                '2102-12-01T22:12:01.000+00:00',
                'UTC',
                '2102-12-01T22:12:01+00:00',
            ],
            'ISO + null'                   => [
                '2102-12-01T22:12:01.000+00:00',
                null,
                '2102-12-01T22:12:01+00:00',
            ],
        ];
    }
    // </editor-fold>
}
