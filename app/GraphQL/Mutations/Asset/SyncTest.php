<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Jobs\AssetSync;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Asset\Sync
 */
class SyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param \Closure(): string $prepare
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $id           = $this->faker->uuid;

        if ($prepare) {
            $id = $prepare($this, $organization, $user);
        } elseif ($organization) {
            Asset::factory()->create([
                'id'          => $id,
                'reseller_id' => Reseller::factory()->create([
                    'id' => $organization->getKey(),
                ]),
            ]);
        } else {
            // empty
        }

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation sync($id: ID!) {
                    asset(id: $id) {
                        sync {
                            result
                        }
                    }
                }',
                [
                    'id' => $id,
                ],
            )
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
            new OrganizationDataProvider('asset'),
            new OrganizationUserDataProvider('asset', [
                'assets-sync',
            ]),
            new ArrayDataProvider([
                'ok'            => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    static function (self $test, Organization $organization): string {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        $asset    = Asset::factory()->create([
                            'reseller_id' => $reseller,
                        ]);

                        $test->override(AssetSync::class, static function (MockInterface $mock) use ($asset): void {
                            $mock->makePartial();
                            $mock
                                ->shouldReceive('init')
                                ->withArgs(static function (Asset $actual) use ($asset): bool {
                                    return $asset->getKey() === $actual->getKey();
                                })
                                ->once()
                                ->andReturnSelf();
                            $mock
                                ->shouldReceive('__invoke')
                                ->once()
                                ->andReturn([
                                    'result' => true,
                                ]);
                        });

                        return $asset->getKey();
                    },
                ],
                'invalid asset' => [
                    new GraphQLError('asset', static function (): Throwable {
                        return new ObjectNotFound((new Asset())->getMorphClass());
                    }),
                    static function (self $test): string {
                        return $test->faker->uuid;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
