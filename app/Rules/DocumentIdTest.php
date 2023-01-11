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
 * @covers \App\Rules\DocumentId
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class DocumentIdTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testMessage(): void {
        $this->app->setLocale('de');
        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'validation.document_id' => 'Translated',
                ],
            ];
        });
        self::assertEquals($this->app->make(DocumentId::class)->message(), 'Translated');
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
        $rule   = $this->app->make(DocumentId::class);
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
            'exists'            => [
                true,
                static function (TestCase $test, Organization $org): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $org,
                    ]);
                    $type     = Type::factory()->create([
                        'id' => $test->faker->randomElement([
                            '5710fd06-95b8-4699-90b1-e0aa678c6028',
                            '0bd82ced-33fa-423a-b7c4-a2cbf27a325a',
                        ]),
                    ]);
                    $document = Document::factory()->create([
                        'reseller_id' => $reseller,
                        'type_id'     => $type->getKey(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.contract_types' => ['5710fd06-95b8-4699-90b1-e0aa678c6028'],
                    'ep.quote_types'    => ['0bd82ced-33fa-423a-b7c4-a2cbf27a325a'],
                ],
            ],
            'exists (quote)'    => [
                true,
                static function (TestCase $test, Organization $org): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $org,
                    ]);
                    $type     = Type::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                    ]);
                    $document = Document::factory()->create([
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.quote_types' => ['f9834bc1-2f2f-4c57-bb8d-7a224ac24983'],
                ],
            ],
            'exists (contract)' => [
                true,
                static function (TestCase $test, Organization $org): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $org,
                    ]);
                    $type     = Type::factory()->create([
                        'id' => '8bc7eaa9-efd6-4a81-8ab1-7fcbd0c870d7',
                    ]);
                    $document = Document::factory()->create([
                        'reseller_id' => $reseller->getKey(),
                        'type_id'     => $type->getKey(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.contract_types' => ['8bc7eaa9-efd6-4a81-8ab1-7fcbd0c870d7'],
                ],
            ],
            'not a document'    => [
                false,
                static function (TestCase $test, Organization $org): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $org,
                    ]);
                    $document = Document::factory()->create([
                        'reseller_id' => $reseller,
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.contract_types' => ['5710fd06-95b8-4699-90b1-e0aa678c6028'],
                    'ep.quote_types'    => ['0bd82ced-33fa-423a-b7c4-a2cbf27a325a'],
                ],
            ],
            'not-exists'        => [
                false,
                static function (): string {
                    return Document::factory()->make()->getKey();
                },
            ],
            'soft-deleted'      => [
                false,
                static function (TestCase $test, Organization $org): string {
                    $reseller = Reseller::factory()->create([
                        'id' => $org,
                    ]);
                    $type     = Type::factory()->create([
                        'id' => '74f07e06-d775-4d03-8ea8-f8467223d91e',
                    ]);
                    $document = Document::factory()->create([
                        'reseller_id' => $reseller,
                        'type_id'     => $type->getKey(),
                        'deleted_at'  => Date::now(),
                    ]);

                    return $document->getKey();
                },
                [
                    'ep.quote_types' => ['74f07e06-d775-4d03-8ea8-f8467223d91e'],
                ],
            ],
            'empty string'      => [
                false,
                static function (): string {
                    return '';
                },
            ],
        ];
    }
    // </editor-fold>
}
