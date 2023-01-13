<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Rules\ContractId
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ContractIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.contract_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(ContractId::class)->message(), 'Translated');
    }

    /**
     * @dataProvider dataProviderPasses
     *
     * @param Closure(static, ?Organization): ?string $valueFactory
     * @param SettingsFactory                         $settingsFactory
     */
    public function testPasses(bool $expected, Closure $valueFactory, mixed $settingsFactory = null): void {
        $this->setSettings($settingsFactory);

        $org    = $this->setOrganization(Organization::factory()->create());
        $rule   = $this->app->make(ContractId::class);
        $value  = $valueFactory($this, $org);
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
            'exists'       => [
                true,
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $type     = Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.contract_types' => ['f9834bc1-2f2f-4c57-bb8d-7a224ac24983'],
                ],
            ],
            'not-exists'   => [
                false,
                static function (): string {
                    return 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982';
                },
            ],
            'soft-deleted' => [
                false,
                static function (TestCase $test, Organization $organization): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $organization->getKey(),
                    ]);
                    $type     = Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $document = Document::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                        'deleted_at'  => Date::now(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.contract_types' => ['f9834bc1-2f2f-4c57-bb8d-7a224ac24983'],
                ],
            ],
            'empty string' => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
