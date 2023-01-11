<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\Boolean
 */
class BooleanTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $rule   = $this->app->make(Boolean::class);
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
                    'validation.boolean' => 'message validation.boolean',
                ],
            ];
        });

        self::assertEquals('message validation.boolean', (new Boolean())->message());
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
            '``'      => [false, ''],
        ];
    }
    // </editor-fold>
}
