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
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

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
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($user) {
            $user->save();
        }

        $userSearchId = 'wrongId';
        if ($user && $dataFactory) {
            $userSearchId = $dataFactory($this, $user);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation DeleteMeSearch($input: DeleteMeSearchInput!) {
                deleteMeSearch(input:$input) {
                    deleted
                }
            }', [ 'input' => [ 'id' => $userSearchId]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $this->assertSoftDeleted((new UserSearch())->getTable(), ['id' => $userSearchId ]);
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
                'ok'        => [
                    new GraphQLSuccess('deleteMeSearch', DeleteMeSearch::class, [
                        'deleted' => '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409a',
                    ]),
                    static function (TestCase $test, User $user): string {
                        UserSearch::factory()
                            ->create([
                                'id'      => '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409a',
                                'user_id' => $user->id,
                            ]);
                        return '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409a';
                    },
                ],
                'Not found' => [
                    new GraphQLError('deleteMeSearch', static function (): array {
                        return [__('graphql.mutations.deleteMeSearch.not_found')];
                    }),
                    static function (TestCase $test, User $user): string {
                        UserSearch::factory()
                            ->create([
                                'id'      => '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409a',
                                'user_id' => $user->id,
                            ]);
                        return '06859f2f-08f0-3f7b-bdcb-bd2cc8e6409b';
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
