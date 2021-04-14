<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\FloatNumber
 */
class FloatNumberTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $this->assertEquals($expected, (new FloatNumber())->passes('test', $value));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.float' => 'message validation.float',
                ],
            ];
        });

        $this->assertEquals('message validation.float', (new FloatNumber())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'bool'      => [false, false],
            'string'    => [false, 'sdf'],
            'string123' => [false, 'sdf123'],
            '"123"'     => [true, '123'],
            '123'       => [true, 123],
            '123.124'   => [true, 123.123],
            '"123,124"' => [false, '123,123'],
            '0'         => [true, 0],
        ];
    }
    // </editor-fold>
}
