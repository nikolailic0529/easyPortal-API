<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Duration
 */
class DurationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $this->assertEquals($expected, (new Duration())->passes('test', $value));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.duration' => 'message validation.duration',
                ],
            ];
        });

        $this->assertEquals('message validation.duration', (new Duration())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'P1Y'                 => [true, 'P1Y'],
            'P1.4Y'               => [false, 'P1.4Y'],
            'P1.4Y2M'             => [false, 'P1.4Y2M'],
            'P14Y2.5M'            => [false, 'P14Y2.5M'],
            'P14Y2.5MT1M'         => [false, 'P14Y2.5MT1M'],
            'P2MT30M'             => [true, 'P2MT30M'],
            'P2MT30.5M'           => [false, 'P2MT30.5M'],
            'P2MT30.5M3S'         => [false, 'P2MT30.5M3S'],
            'PT6H'                => [true, 'PT6H'],
            'PT6.5H'              => [false, 'PT6.5H'],
            'PT6.5H12S'           => [false, 'PT6.5H12S'],
            'P5W'                 => [true, 'P5W'],
            'P5.2W'               => [false, 'P5.2W'],
            'P5.2W1'              => [false, 'P5.2W1D'],
            'P3Y29DT4H35M59S'     => [true, 'P3Y29DT4H35M59S'],
            'P1Y1M1DT2H12M34.23S' => [false, 'P1Y1M1DT2H12M34.23S'],
            'P'                   => [false, 'P'],
            'PT'                  => [false, 'PT'],
            'P3MT'                => [false, 'P3MT'],
        ];
    }
    // </editor-fold>
}