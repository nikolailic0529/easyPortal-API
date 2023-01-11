<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\HashMap
 */
class HashMapTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $rule   = $this->app->make(HashMap::class);
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
                    'validation.hash_map' => 'message validation.hash_map',
                ],
            ];
        });

        self::assertEquals('message validation.hash_map', (new HashMap())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'bool'        => [false, true],
            'empty array' => [true, []],
            'list'        => [false, ['a']],
            'hash'        => [true, ['a' => 1]],
            'mixed'       => [false, ['a' => 1, 2]],
            '``'          => [false, ''],
        ];
    }
    // </editor-fold>
}
