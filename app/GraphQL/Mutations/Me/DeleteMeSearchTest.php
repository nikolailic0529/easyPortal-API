<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\User;
use App\Models\UserSearch;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Me\DeleteMeSearch
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteMeSearchTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $dataFactory = null,
        bool $exists = null,
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setOrganization($orgFactory));

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
            }', ['input' => ['id' => $userSearch?->getKey() ?: $this->faker->uuid()]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertEquals($exists, $userSearch->exists());
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
            new AuthOrgDataProvider('deleteMeSearch'),
            new AuthMeDataProvider('deleteMeSearch'),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('deleteMeSearch', [
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
                    new GraphQLSuccess('deleteMeSearch', [
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
