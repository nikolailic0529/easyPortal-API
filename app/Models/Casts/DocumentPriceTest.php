<?php declare(strict_types = 1);

namespace App\Models\Casts;

use App\Models\Document;
use App\Models\DocumentEntry;
use Closure;
use Exception;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Models\Casts\DocumentPrice
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class DocumentPriceTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param SettingsFactory                           $settingsFactory
     * @param Closure(static): (Document|DocumentEntry) $factory
     */
    public function testSet(
        Exception|string|null $expected,
        mixed $settingsFactory,
        Closure $factory,
        string $attr,
        mixed $value,
    ): void {
        $this->setSettings($settingsFactory);

        $object = $factory($this)->mergeCasts([
            $attr => DocumentPrice::class,
        ]);

        $object->setAttribute($attr, $value);

        self::assertEquals($expected, $object->getAttribute($attr));
    }
    //</editor-fold>

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
                        ->create();
                },
                'price',
                null,
            ],
            'Document: price'         => [
                '123.45',
                [
                    'ep.document_statuses_no_price' => [
                        'f050820e-7853-4e00-8ef9-32b9f1303c27',
                    ],
                ],
                static function (): Document {
                    return Document::factory()->create();
                },
                'price',
                '123.45',
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
                        ->create();
                },
                'price',
                '123.45',
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
                    ]);

                    return $entry;
                },
                'list_price',
                null,
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
                    ]);

                    return $entry;
                },
                'list_price',
                '123.45',
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
                    ]);

                    return $entry;
                },
                'list_price',
                '123.45',
            ],
        ];
    }
    // </editor-fold>
}
