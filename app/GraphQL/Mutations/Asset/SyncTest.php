<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Services\DataLoader\Jobs\AssetSync;
use Closure;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

use function array_filter;
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
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        Queue::fake();

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation sync($input: [AssetSyncInput!]!) {
                    asset {
                        sync(input: $input) {
                            result
                        }
                    }
                }',
                [
                    'input' => $input ?: [['id' => '79b91f78-c244-4e95-a99d-bf8b15255591']],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Queue::assertPushed(AssetSync::class, count($input));

            foreach ($input as $call) {
                Queue::assertPushed(AssetSync::class, static function (AssetSync $job) use ($call): bool {
                    $params = [
                        'id'        => $job->getAssetId(),
                        'documents' => $job->getDocuments(),
                    ];
                    $params = array_filter($params, static fn(mixed $value): bool => $value !== null);
                    $pushed = $call === $params;

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
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('asset'),
            new RootUserDataProvider('asset'),
            new ArrayDataProvider([
                'ok'                  => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                        ],
                        [
                            'id' => '2181735f-42b6-41bf-a069-47a88883b239',
                        ],
                    ],
                ],
                'ok (with documents)' => [
                    new GraphQLSuccess(
                        'asset',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id'        => 'ef317ed7-fc3c-439d-9679-a6248bf6e69c',
                            'documents' => true,
                        ],
                        [
                            'id'        => '4e15b024-40f8-4340-a68b-c3ba8c993e66',
                            'documents' => false,
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
