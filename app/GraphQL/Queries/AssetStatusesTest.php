<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Status;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\AssetStatuses
 */
class AssetStatusesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $translationsFactory = null,
        Closure $statusesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($statusesFactory) {
            $statusesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                assetStatuses {
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
        return (new CompositeDataProvider(
            new OrganizationDataProvider('customerStatuses'),
            new AuthUserDataProvider('customerStatuses'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('assetStatuses', AssetStatuses::class, [
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                        ],
                        [
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name' => 'Translated (locale)',
                        ],
                        [
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name' => 'Translated (fallback)',
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        $model = (new Status())->getMorphClass();
                        $type  = (new Asset())->getMorphClass();

                        return [
                            $locale => [
                                "models.{$model}.name.{$type}.translated"          => 'Translated (locale)',
                                "models.{$model}.name.{$type}.translated-fallback" => 'Translated (fallback)',
                            ],
                        ];
                    },
                    static function (TestCase $test): void {
                        Status::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'        => 'No translation',
                            'object_type' => (new Asset())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'key'         => 'translated',
                            'name'        => 'Should be translated',
                            'object_type' => (new Asset())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'key'         => 'translated-fallback',
                            'name'        => 'Should be translated via fallback',
                            'object_type' => (new Asset())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'name' => 'Wrong object_type',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
