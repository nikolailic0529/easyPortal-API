<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Data;

use App\Models\Language;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class LanguagesTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $translationsFactory = null,
        Closure $languagesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setTranslations($translationsFactory);

        if ($languagesFactory) {
            $languagesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                languages(where: {documents: { where: {}, count: {lessThan: 1} }}) {
                    id
                    name
                    code
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
        return (new CompositeDataProvider(
            new AuthOrgDataProvider('languages'),
            new AuthMeDataProvider('languages'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('languages', [
                        [
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name' => 'Translated (locale)',
                            'code' => 'c1',
                        ],
                        [
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name' => 'Translated (fallback)',
                            'code' => 'c2',
                        ],
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                            'code' => 'c3',
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        $model = (new Language())->getMorphClass();

                        return [
                            $locale => [
                                "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                                "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
                            ],
                        ];
                    },
                    static function (): void {
                        Language::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'code' => 'c3',
                            'name' => 'No translation',
                        ]);
                        Language::factory()->create([
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'code' => 'c1',
                            'name' => 'Should be translated',
                        ]);
                        Language::factory()->create([
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'code' => 'c2',
                            'name' => 'Should be translated via fallback',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
