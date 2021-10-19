<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\Services\DataLoader\Jobs\CustomerSync;
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
 * @coversDefaultClass \App\GraphQL\Mutations\Customer\Sync
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
                mutation sync($input: [CustomerSyncInput!]!) {
                    customer {
                        sync(input: $input) {
                            result
                        }
                    }
                }',
                [
                    'input' => $input ?: [['id' => '8b79a366-f9c2-4eb1-b8e5-5423bc333f96']],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Queue::assertPushed(CustomerSync::class, count($input));

            foreach ($input as $call) {
                Queue::assertPushed(CustomerSync::class, static function (CustomerSync $job) use ($call): bool {
                    $params = [
                        'id'        => $job->getCustomerId(),
                        'assets'    => $job->getAssets(),
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
            new RootOrganizationDataProvider('customer'),
            new RootUserDataProvider('customer'),
            new ArrayDataProvider([
                'ok'                             => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id' => '981edfa2-2139-42f6-bc7a-f7ff66df52ad',
                        ],
                        [
                            'id' => 'd840dfdb-7c9a-4324-8470-12ec91199834',
                        ],
                    ],
                ],
                'ok (with assets)'               => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id'     => '5985f4ce-f4a2-4cf2-afb7-2959fc126785',
                            'assets' => true,
                        ],
                    ],
                ],
                'ok (with assets and documents)' => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result' => true,
                        ]),
                    ),
                    [
                        [
                            'id'        => 'ac1a2af5-2f07-47d4-a390-8d701ce50a13',
                            'assets'    => true,
                            'documents' => true,
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
