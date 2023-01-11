<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\DataLoader\Queue\Tasks\CustomerSync;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgResellerDataProvider;
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
 * @covers \App\GraphQL\Mutations\Customer\Sync
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
            $id = Customer::factory()->ownedBy($org)->create()->getKey();
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
            new AuthOrgResellerDataProvider('customer'),
            new OrgUserDataProvider('customer', [
                'customers-sync',
            ]),
            new ArrayDataProvider([
                'ok'               => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragment('sync', [
                            'result'   => true,
                            'warranty' => true,
                        ]),
                    ),
                    static function (TestCase $test, Organization $org): string {
                        $customer = Customer::factory()->ownedBy($org)->create();

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
                        return $test->faker->uuid();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
