<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Models\UserSearch;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteMeSearch
 */
class DeleteMeSearchTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $dataFactory = null,
        bool $exists = null,
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($user) {
            $user->save();
        }

        $userSearch = null;

        if ($user && $dataFactory) {
            $userSearch = $dataFactory($this, $user);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation DeleteMeSearch($input: DeleteMeSearchInput!) {
                deleteMeSearch(input:$input) {
                    deleted
                }
            }', ['input' => ['id' => $userSearch?->getKey() ?: $this->faker->uuid]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $this->assertEquals($exists, $userSearch->exists());
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
            new TenantDataProvider(),
            new UserDataProvider('deleteMeSearch'),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('deleteMeSearch', self::class, [
                        'deleted' => true,
                    ]),
                    static function (TestCase $test, User $user): UserSearch {
                        return UserSearch::factory()->create([
                            'user_id' => $user->getKey(),
                        ]);
                    },
                    false,
                ],
                'Different User' => [
                    new GraphQLSuccess('deleteMeSearch', self::class, [
                        'deleted' => false,
                    ]),
                    static function (TestCase $test, User $user): UserSearch {
                        return UserSearch::factory()->for(User::factory())->create();
                    },
                    true,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
