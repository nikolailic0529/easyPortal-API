<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use App\Models\DocumentEntry;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Mockery;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;
use Tests\WithSettings;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Documents\Price
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class PriceTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param SettingsFactory                           $settingsFactory
     * @param Closure(static): (Document|DocumentEntry) $factory
     */
    public function testInvoke(?string $expected, mixed $settingsFactory, Closure $factory, string $field): void {
        $this->setSettings($settingsFactory);

        $info            = Mockery::mock(ResolveInfo::class);
        $info->fieldName = $field;
        $resolver        = $this->app->make(Price::class);
        $context         = Mockery::mock(GraphQLContext::class);
        $object          = $factory($this);
        $actual          = ($resolver)($object, [], $context, $info);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<string, array{?string, array<string, mixed>, Closure(static): (Document|DocumentEntry)}>
     */
    public function dataProviderInvoke(): array {
        return [
            'Document: no price'      => [
                null,
                [
                    'ep.document_statuses_no_price' => [
                        '2887aa06-7174-44b2-b0b0-576f65a9cf3a',
                    ],
                ],
                static function (): Document {
                    return Document::factory()
                        ->hasStatuses(1, [
                            'id' => '2887aa06-7174-44b2-b0b0-576f65a9cf3a',
                        ])
                        ->create([
                            'price' => null,
                        ]);
                },
                'price',
            ],
            'Document: price'         => [
                '123.45',
                [
                    'ep.document_statuses_no_price' => [
                        'f050820e-7853-4e00-8ef9-32b9f1303c27',
                    ],
                ],
                static function (): Document {
                    return Document::factory()
                        ->hasStatuses(1, [
                            'id' => 'd6b0daf7-6fe3-4e9c-bb45-1d771ee756c6',
                        ])
                        ->create([
                            'price' => '123.45',
                        ]);
                },
                'price',
            ],
            'Document: hidden'        => [
                null,
                [
                    'ep.document_statuses_no_price' => [
                        '42f575a5-3f15-4281-ac3a-bd408521650c',
                    ],
                ],
                static function (): Document {
                    return Document::factory()
                        ->hasStatuses(1, [
                            'id' => '42f575a5-3f15-4281-ac3a-bd408521650c',
                        ])
                        ->create([
                            'price' => '123.45',
                        ]);
                },
                'price',
            ],
            'DocumentEntry: no price' => [
                null,
                [
                    'ep.document_statuses_no_price' => [
                        'd70b179f-b788-4e15-a9ac-c25a3e9d48e4',
                    ],
                ],
                static function (): DocumentEntry {
                    $document = Document::factory()
                        ->hasStatuses(1, [
                            'id' => 'd70b179f-b788-4e15-a9ac-c25a3e9d48e4',
                        ])
                        ->create();
                    $entry    = DocumentEntry::factory()->create([
                        'document_id' => $document,
                        'list_price'  => null,
                    ]);

                    return $entry;
                },
                'list_price',
            ],
            'DocumentEntry: price'    => [
                '123.45',
                [
                    'ep.document_statuses_no_price' => [
                        '974cad03-3852-4e1c-98d8-4e6e165f4a2b',
                    ],
                ],
                static function (): DocumentEntry {
                    $document = Document::factory()->create();
                    $entry    = DocumentEntry::factory()->create([
                        'document_id' => $document,
                        'list_price'  => '123.45',
                    ]);

                    return $entry;
                },
                'list_price',
            ],
            'DocumentEntry: hidden'   => [
                null,
                [
                    'ep.document_statuses_no_price' => [
                        '3c4b6422-4dcf-4162-989d-95145feba60a',
                    ],
                ],
                static function (): DocumentEntry {
                    $document = Document::factory()
                        ->hasStatuses(1, [
                            'id' => '3c4b6422-4dcf-4162-989d-95145feba60a',
                        ])
                        ->create();
                    $entry    = DocumentEntry::factory()->create([
                        'document_id' => $document,
                        'list_price'  => '123.45',
                    ]);

                    return $entry;
                },
                'list_price',
            ],
        ];
    }
    // </editor-fold>
}
