<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Type;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\QuoteTypeId
 */
class QuoteTypeIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->app->setLocale('de');
        $translationsFactory = static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.quote_type_id' => 'Translated',
                ],
            ];
        };
        $this->setTranslations($translationsFactory);
        self::assertEquals($this->app->make(QuoteTypeId::class)->message(), 'Translated');
    }

    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): Type $typeFactory
     */
    public function testPasses(bool $expected, Closure $typeFactory): void {
        $type = $typeFactory($this);
        $rule = $this->app->make(QuoteTypeId::class);

        self::assertEquals($expected, $rule->passes('test', $type->getKey()));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'exists'               => [
                true,
                static function (TestCase $test): Type {
                    $type = Type::factory()->create();

                    $test->setSettings([
                        'ep.quote_types' => [$type->getKey()],
                    ]);

                    return $type;
                },
            ],
            'exists but not quote' => [
                false,
                static function (): Type {
                    return Type::factory()->create();
                },
            ],
            'exists but contract'  => [
                false,
                static function (TestCase $test): Type {
                    $type = Type::factory()->create();

                    $test->setSettings([
                        'ep.contract_types' => [$type->getKey()],
                    ]);

                    return $type;
                },
            ],
            'not-exists'           => [
                false,
                static function (): Type {
                    return Type::factory()->make();
                },
            ],
            'soft-deleted'         => [
                false,
                static function (): Type {
                    $type = Type::factory()->create();
                    $type->delete();

                    return $type;
                },
            ],
        ];
    }
    // </editor-fold>
}
