<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\DocumentEntryField;
use App\Models\Field;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Utils\WithTranslations;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Quotes\QuoteEntryFields
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type TranslationsFactory from WithTranslations
 */
class QuoteEntryFieldsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory   $orgFactory
     * @param UserFactory           $userFactory
     * @param TranslationsFactory   $translationsFactory
     * @param Closure(static): void $fieldsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $translationsFactory = null,
        Closure $fieldsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setTranslations($translationsFactory);

        if ($fieldsFactory) {
            $fieldsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                quoteEntryFields(where: { quotes: { where: {}, count: {lessThan: 1} }}) {
                    id
                    name
                }
            }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $provider = new ArrayDataProvider([
            'ok' => [
                new GraphQLSuccess('quoteEntryFields', [
                    [
                        'id'   => '17882f6e-eaf3-49a4-a694-8c6eaa826e9c',
                        'name' => 'Translated (locale)',
                    ],
                    [
                        'id'   => '720ee3af-f2b2-4c55-8d24-08dae402b425',
                        'name' => 'No translation',
                    ],
                    [
                        'id'   => 'c4829ab0-3687-4662-a251-d91ab2886272',
                        'name' => 'Translated (fallback)',
                    ],
                ]),
                static function (TestCase $test, string $locale): array {
                    $model = (new Field())->getMorphClass();

                    return [
                        $locale => [
                            "models.{$model}.17882f6e-eaf3-49a4-a694-8c6eaa826e9c.name" => 'Translated (locale)',
                            "models.{$model}.c4829ab0-3687-4662-a251-d91ab2886272.name" => 'Translated (fallback)',
                        ],
                    ];
                },
                static function (TestCase $test): void {
                    $type = (new DocumentEntryField())->getMorphClass();

                    Field::factory()->create([
                        'id'          => '720ee3af-f2b2-4c55-8d24-08dae402b425',
                        'name'        => 'No translation',
                        'object_type' => $type,
                    ]);
                    Field::factory()->create([
                        'id'          => '17882f6e-eaf3-49a4-a694-8c6eaa826e9c',
                        'key'         => 'translated',
                        'name'        => 'Should be translated',
                        'object_type' => $type,
                    ]);
                    Field::factory()->create([
                        'id'          => 'c4829ab0-3687-4662-a251-d91ab2886272',
                        'key'         => 'translated-fallback',
                        'name'        => 'Should be translated via fallback',
                        'object_type' => $type,
                    ]);
                    Field::factory()->create([
                        'name'        => 'Wrong object_type',
                        'object_type' => 'unknown',
                    ]);
                },
            ],
        ]);

        return (new MergeDataProvider([
            'quotes-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('quoteEntryFields'),
                new OrgUserDataProvider('quoteEntryFields', [
                    'quotes-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
