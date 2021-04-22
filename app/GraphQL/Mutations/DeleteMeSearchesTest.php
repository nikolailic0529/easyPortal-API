<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Models\UserSearch;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DeleteMeSearches
 */
class DeleteMeSearchesTest extends TestCase {
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
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($user) {
            $user->save();
        }

        $key = null;
        if ($user && $dataFactory) {
            $key = $dataFactory($this, $user);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation DeleteMeSearches($input: DeleteMeSearchesInput!) {
                deleteMeSearches(input:$input) {
                    deleted
                }
            }', [ 'input' => [ 'key' => $key]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            if ($key) {
                $this->assertDatabaseMissing((new UserSearch())->getTable(), ['key' => $key, 'deleted_at' => null]);
            } else {
                $this->assertEmpty($user->searches);
            }
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
            new UserDataProvider('deleteMeSearches'),
            new ArrayDataProvider([
                'single'         => [
                    new GraphQLSuccess('deleteMeSearches', DeleteMeSearches::class, [
                        'deleted' => ['key'],
                    ]),
                    static function (TestCase $test, User $user): ?string {
                        $search = UserSearch::factory()
                            ->create([
                                'id'      => '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409a',
                                'key'     => 'key',
                                'user_id' => $user->id,
                            ]);
                        return $search->key;
                    },
                ],
                'multiple'       => [
                    new GraphQLSuccess('deleteMeSearches', DeleteMeSearches::class, [
                        'deleted' => ['multiple_key'],
                    ]),
                    static function (TestCase $test, User $user): ?string {
                        UserSearch::factory()
                            ->for($user)
                            ->count(2)
                            ->create([
                                'key' => 'multiple_key',
                            ]);
                        return null;
                    },
                ],
                'Different user' => [
                    new GraphQLSuccess('deleteMeSearches', DeleteMeSearches::class, [
                        'deleted' => [],
                    ]),
                    static function (TestCase $test, User $user): ?string {
                        UserSearch::factory()
                            ->for(User::factory())
                            ->create([
                                'key' => 'key',
                            ]);
                        return null;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
