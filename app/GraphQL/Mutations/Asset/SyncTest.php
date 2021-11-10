<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use App\Services\DataLoader\Jobs\AssetSync;
use Closure;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

use function __;
use function count;

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
     * @param array<mixed> $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $input = [],
        Closure $prepare = null,
    ): void {
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $id           = $this->faker->uuid;

        if ($prepare) {
            $prepare($this, $organization, $user, $input);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }

            $reseller = Reseller::factory()->create([
                'id' => $organization ? $organization->getKey() : $this->faker->uuid,
            ]);

            Asset::factory()->create([
                'id'          => $id,
                'reseller_id' => $reseller,
            ]);
        }

        Queue::fake();

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation sync($input: AssetSyncInput!) {
                    asset {
                        sync(input: $input) {
                            result
                        }
                    }
                }',
                [
                    'input' => $input ?: ['id' => $id],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Queue::assertPushed(AssetSync::class, count($input['id'] ?? []));

            foreach ((array) ($input['id'] ?? []) as $assetId) {
                Queue::assertPushed(AssetSync::class, static function (AssetSync $job) use ($assetId): bool {
                    $arguments = [
                        'warranty-check' => true,
                        'documents'      => true,
                    ];
                    $pushed    = $job->getObjectId() === $assetId && $job->getArguments() === $arguments;

                    return $pushed;
                });
            }
        } else {
            Queue::assertNothingPushed();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $factory = static function (TestCase $test, Organization $organization, User $user, array $input): void {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            foreach ((array) $input['id'] as $id) {
                Asset::factory()->create([
                    'id'          => $id,
                    'reseller_id' => $reseller,
                ]);
            }
        };

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
                    [
                        'id' => [
                            '90398f16-036f-4e6b-af90-06e19614c57c',
                            '2181735f-42b6-41bf-a069-47a88883b239',
                        ],
                    ],
                    $factory,
                ],
                'invalid asset' => [
                    new GraphQLError('asset', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'id' => '05bb78da-8a67-4ed4-a1a5-db80a75d66e9',
                    ],
                    static function (): void {
                        // empty
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
