<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Boolean
 */
class BooleanTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $this->assertEquals($expected, (new Boolean())->passes('test', $value));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.boolean' => 'message validation.boolean',
                ],
            ];
        });

        $this->assertEquals('message validation.boolean', (new Boolean())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'bool'    => [true, true],
            'false'   => [true, false],
            'true'    => [true, true],
            '"true"'  => [true, 'true'],
            '"false"' => [true, 'false'],
            '1'       => [false, 1],
            '"1"'     => [false, '1'],
        ];
    }
    // </editor-fold>
}