<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Team;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class TeamsTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $teamsFactory = null,
        Closure $translationsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($teamsFactory) {
            $teamsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                teams {
                    id
                    name
                    key
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
        return (new MergeDataProvider([
            'administer'     => new CompositeDataProvider(
                new OrganizationDataProvider('teams'),
                new OrganizationUserDataProvider('teams', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('teams', self::class, [
                            [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'Translated (locale)',
                                'key'  => 'translated',
                            ],
                            [
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'name' => 'Translated (fallback)',
                                'key'  => 'translated-fallback',
                            ],
                            [
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name' => 'No translation',
                                'key'  => 'No translation',
                            ],
                        ]),
                        static function (): void {
                            Team::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'Should be translated',
                                'key'  => 'translated',
                            ]);
                            Team::factory()->create([
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'key'  => 'translated-fallback',
                                'name' => 'Should be translated via fallback',
                            ]);
                            Team::factory()->create([
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name' => 'No translation',
                                'key'  => 'No translation',
                            ]);
                        },
                        static function (TestCase $test, string $locale): array {
                            $model = (new Team())->getMorphClass();
                            $key1  = "models.{$model}.439a0a06-d98a-41f0-b8e5-4e5722518e00.name";
                            $key2  = "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name";
                            return [
                                $locale => [
                                    $key1 => 'Translated (locale)',
                                    $key2 => 'Translated (fallback)',
                                ],
                            ];
                        },
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new OrganizationDataProvider('teams'),
                new OrganizationUserDataProvider('teams', [
                    'org-administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('teams', self::class, [
                            [
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'Translated (locale)',
                                'key'  => 'translated',
                            ],
                            [
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'name' => 'Translated (fallback)',
                                'key'  => 'translated-fallback',
                            ],
                            [
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name' => 'No translation',
                                'key'  => 'No translation',
                            ],
                        ]),
                        static function (): void {
                            Team::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name' => 'Should be translated',
                                'key'  => 'translated',
                            ]);
                            Team::factory()->create([
                                'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                                'key'  => 'translated-fallback',
                                'name' => 'Should be translated via fallback',
                            ]);
                            Team::factory()->create([
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name' => 'No translation',
                                'key'  => 'No translation',
                            ]);
                        },
                        static function (TestCase $test, string $locale): array {
                            $model = (new Team())->getMorphClass();
                            $key1  = "models.{$model}.439a0a06-d98a-41f0-b8e5-4e5722518e00.name";
                            $key2  = "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name";
                            return [
                                $locale => [
                                    $key1 => 'Translated (locale)',
                                    $key2 => 'Translated (fallback)',
                                ],
                            ];
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
