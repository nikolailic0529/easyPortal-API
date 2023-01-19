<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Type;
use App\Models\Document;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Rules\QuoteTypeId
 *
 * @phpstan-import-type SettingsFactory from WithSettings
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
     * @param SettingsFactory          $settingsFactory
     * @param Closure(static): ?string $valueFactory
     */
    public function testPasses(bool $expected, mixed $settingsFactory, Closure $valueFactory): void {
        $this->setSettings($settingsFactory);

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
                [
                    'ep.contract_types' => [
                        // empty
                    ],
                    'ep.quote_types'    => [
                        '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                    ],
                ],
                static function (): string {
                    return Type::factory()
                        ->create([
                            'id'          => '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                            'object_type' => (new Document())->getMorphClass(),
                        ])
                        ->getKey();
                },
            ],
            'exists but not quote' => [
                false,
                [
                    // empty
                ],
                static function (): string {
                    return Type::factory()->create()->getKey();
                },
            ],
            'exists but contract'  => [
                false,
                [
                    'ep.contract_types' => [
                        '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                    ],
                    'ep.quote_types'    => [
                        // empty
                    ],
                ],
                static function (): string {
                    return Type::factory()
                        ->create([
                            'id'          => '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                            'object_type' => (new Document())->getMorphClass(),
                        ])
                        ->getKey();
                },
            ],
            'not-exists'           => [
                false,
                [
                    // empty
                ],
                static function (): string {
                    return '2421672e-0fc8-45ad-af62-9c6386078663';
                },
            ],
            'soft-deleted'         => [
                false,
                [
                    'ep.contract_types' => [
                        // empty
                    ],
                    'ep.quote_types'    => [
                        '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                    ],
                ],
                static function (): string {
                    return Type::factory()
                        ->create([
                            'id'          => '171edb3f-dfbf-4a3c-b33c-9d343709527a',
                            'object_type' => (new Document())->getMorphClass(),
                            'deleted_at'  => Date::now(),
                        ])
                        ->getKey();
                },
            ],
            'empty value'          => [
                false,
                [
                    // empty
                ],
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
