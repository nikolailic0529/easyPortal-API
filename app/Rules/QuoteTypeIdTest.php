<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Type;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\QuoteTypeId
 */
class QuoteTypeIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static): ?string $valueFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory): void {
        $rule   = $this->app->make(QuoteTypeId::class);
        $value  = $valueFactory($this);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
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
                static function (TestCase $test): string {
                    $type = Type::factory()->create();

                    $test->setSettings([
                        'ep.quote_types' => [$type->getKey()],
                    ]);

                    return $type->getKey();
                },
            ],
            'exists but not quote' => [
                false,
                static function (): string {
                    return Type::factory()->create()->getKey();
                },
            ],
            'exists but contract'  => [
                false,
                static function (TestCase $test): string {
                    $type = Type::factory()->create();

                    $test->setSettings([
                        'ep.contract_types' => [$type->getKey()],
                    ]);

                    return $type->getKey();
                },
            ],
            'not-exists'           => [
                false,
                static function (): string {
                    return '2421672e-0fc8-45ad-af62-9c6386078663';
                },
            ],
            'soft-deleted'         => [
                false,
                static function (): string {
                    return Type::factory()
                        ->create([
                            'deleted_at' => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty value'          => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
