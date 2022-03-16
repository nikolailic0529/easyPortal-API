<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use App\Services\DataLoader\Jobs\CustomerSync;
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
     * @param Closure(): string $prepare
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
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $customer = Customer::factory()->create([
                'id' => $id,
            ]);

            $reseller->customers()->attach($customer);
        } else {
            // empty
        }

        $this
            ->graphQL(
            /** @lang GraphQL */
                '
                mutation sync($id: ID!) {
                    customer(id: $id) {
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
            new OrganizationDataProvider('customer'),
            new  OrganizationUserDataProvider('customer', [
                'customers-sync',
            ]),
            new ArrayDataProvider([
                'ok'               => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragmentSchema('sync', self::class),
                        new JsonFragment('sync', [
                            'result'   => true,
                            'warranty' => true,
                        ]),
                    ),
                    static function (TestCase $test, Organization $organization, User $user): string {
                        $reseller = Reseller::factory()->create([
                            'id' => $organization->getKey(),
                        ]);
                        $customer = Customer::factory()->create();

                        $reseller->customers()->attach($customer);

                        $test->override(
                            CustomerSync::class,
                            static function (MockInterface $mock) use ($customer): void {
                                $mock->makePartial();
                                $mock
                                    ->shouldReceive('init')
                                    ->withArgs(static function (Customer $actual) use ($customer): bool {
                                        return $customer->getKey() === $actual->getKey();
                                    })
                                    ->once()
                                    ->andReturnSelf();
                                $mock
                                    ->shouldReceive('__invoke')
                                    ->once()
                                    ->andReturn([
                                        'result'   => true,
                                        'warranty' => true,
                                    ]);
                            },
                        );

                        return $customer->getKey();
                    },
                ],
                'invalid customer' => [
                    new GraphQLError('customer', static function (): Throwable {
                        return new ObjectNotFound((new Customer())->getMorphClass());
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
