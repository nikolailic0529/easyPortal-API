<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\DocumentEntry;
use App\Models\Field as FieldModel;
use Closure;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;
use Tests\WithSettings;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Documents\Field
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class FieldTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param Closure(static): DocumentEntry $factory
     */
    public function testInvoke(?string $expected, string $id, Closure $factory): void {
        $resolver = $this->app->make(Field::class);
        $entry    = $factory($this);
        $actual   = $resolver($entry, ['field_id' => $id]);

        self::assertEquals($expected, $actual->field_id ?? null);
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<string, array{?string, string, Closure(static): DocumentEntry}>
     */
    public function dataProviderInvoke(): array {
        return [
            'field unknown' => [
                null,
                '40132692-1798-432e-9ec1-45dd0ac7b7fd',
                static function (): DocumentEntry {
                    return DocumentEntry::factory()
                        ->hasFields(1, [
                            'field_id' => FieldModel::factory()->create([
                                'id' => '3cc13c09-d643-4ab3-ad7d-37e4bc9a23e2',
                            ]),
                        ])
                        ->create();
                },
            ],
            'field known'   => [
                '52f3ddfa-004d-497d-bf52-a27dfd8d0027',
                '52f3ddfa-004d-497d-bf52-a27dfd8d0027',
                static function (): DocumentEntry {
                    return DocumentEntry::factory()
                        ->hasFields(1, [
                            'field_id' => FieldModel::factory()->create([
                                'id' => '52f3ddfa-004d-497d-bf52-a27dfd8d0027',
                            ]),
                        ])
                        ->create();
                },
            ],
        ];
    }
    // </editor-fold>
}
