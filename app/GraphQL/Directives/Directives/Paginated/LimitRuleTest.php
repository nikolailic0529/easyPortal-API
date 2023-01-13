<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Paginated\LimitRule
 */
class LimitRuleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, ?int $limit, mixed $value): void {
        $this->setSettings([
            'ep.pagination.limit.max' => $limit,
        ]);

        self::assertEquals($expected, $this->app->make(LimitRule::class)->passes('test', $value));
    }

    public function testMessage(): void {
        $this->setSettings([
            'ep.pagination.limit.max' => 123,
        ]);
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.max.numeric' => 'validation.max.numeric :max',
                ],
            ];
        });

        self::assertEquals(
            'validation.max.numeric 123',
            $this->app->make(LimitRule::class)->message(),
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
            'bool'      => [false, null, false],
            'string'    => [false, null, 'sdf'],
            'string123' => [false, null, 'sdf123'],
            '"1234"'    => [false, null, '1234'],
            '123'       => [true, null, 123],
            '123.124'   => [false, null, 23.123],
            '"123,124"' => [false, null, '123,123'],
            'zero'      => [true, null, 0],
            'too big'   => [false, 10, 20],
        ];
    }
    // </editor-fold>
}
