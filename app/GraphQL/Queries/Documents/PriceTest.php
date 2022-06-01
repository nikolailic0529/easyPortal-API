<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use Closure;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Documents\Price
 */
class PriceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string, mixed>      $settings
     * @param Closure(static): Document $factory
     */
    public function testInvoke(?string $expected, array $settings, Closure $factory): void {
        $this->setSettings($settings);

        $document = $factory($this);
        $resolver = $this->app->make(Price::class);
        $actual   = ($resolver)($document);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<string, array{?string, array<string, mixed>, Closure(static): Document}>
     */
    public function dataProviderInvoke(): array {
        return [
            'no price' => [
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
            ],
            'price'    => [
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
            ],
            'hidden'   => [
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
            ],
        ];
    }
    // </editor-fold>
}
