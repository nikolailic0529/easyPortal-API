<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use App\Services\DataLoader\Queue\Tasks\AssetSync;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;
use Throwable;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Asset\Sync
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                           $orgFactory
     * @param UserFactory                                   $userFactory
     * @param Closure(static, ?Organization, ?User): string $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $id   = $this->faker->uuid();

        if ($prepare) {
            $id = $prepare($this, $org, $user);
        } elseif ($org) {
            Asset::factory()->ownedBy($org)->create([
                'id' => $id,
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
                            warranty
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
            new AuthOrgDataProvider('asset'),
            new OrgUserDataProvider('asset', [
                'assets-sync',
            ]),
            new ArrayDataProvider([
                'ok'            => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragment('sync', [
                            'result'   => true,
                            'warranty' => true,
                        ]),
                    ),
                    static function (self $test, Organization $org): string {
                        $asset = Asset::factory()->ownedBy($org)->create();

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
                                    'warranty' => true,
                                    'result'   => true,
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
                        return $test->faker->uuid();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
