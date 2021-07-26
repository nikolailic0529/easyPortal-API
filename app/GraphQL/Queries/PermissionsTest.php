<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Permission;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class PermissionsTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $translationsFactory = null,
        Closure $permissionsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($permissionsFactory) {
            $permissionsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                permissions {
                    id
                    name
                    key
                    description
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
            new OrganizationDataProvider('permissions'),
            new UserDataProvider('permissions', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('permissions', self::class, [
                        [
                            'id'          => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name'        => 'translated-name',
                            'key'         => 'assets-view',
                            'description' => 'translated-description',
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        $id    = '439a0a06-d98a-41f0-b8e5-4e5722518e00';
                        $model = (new Permission())->getMorphClass();

                        return [
                            $locale => [
                                "models.{$model}.{$id}.name"        => 'translated-name',
                                "models.{$model}.{$id}.description" => 'translated-description',
                            ],
                        ];
                    },
                    static function (): void {
                        Permission::factory()->create([
                            'id'  => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'key' => 'assets-view',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
