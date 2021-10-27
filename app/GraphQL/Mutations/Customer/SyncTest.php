<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use App\Services\DataLoader\Jobs\CustomerSync;
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
            $customer = Customer::factory()->create([
                'id' => $id,
            ]);

            $reseller->customers()->attach($customer);
        }

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
                    'input' => $input ?: [['id' => $id]],
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
        $factory = static function (TestCase $test, Organization $organization, User $user, array $input): void {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            foreach ($input as $call) {
                $customer = Customer::factory()->create([
                    'id' => $call['id'],
                ]);

                $reseller->customers()->attach($customer);
            }
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('customer'),
            new  OrganizationUserDataProvider('customer', [
                'customers-sync',
            ]),
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
                    $factory,
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
                    $factory,
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
                    $factory,
                ],
                'invalid customer'               => [
                    new GraphQLError('customer', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        [
                            'id' => 'd2ff874e-5c60-4016-a6d0-f0b970b38b17',
                        ],
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