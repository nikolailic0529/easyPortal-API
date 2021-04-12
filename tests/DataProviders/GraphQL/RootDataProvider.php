<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLError;
use Tests\TestCase;

use function __;

/**
 * Only root cat perform the action.
 *
 * @see \Config\Constants::EP_ROOT_USER_ID
 */
class RootDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed' => [
                new ExpectedFinal(new GraphQLError($root, static function (): array {
                    return [__('errors.unauthenticated')];
                })),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed'  => [
                new ExpectedFinal(new GraphQLError($root, static function (): array {
                    return [__('errors.unauthorized')];
                })),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'root is allowed'      => [
                new Unknown(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    $user = User::factory()->make();

                    $test->app()->make(Repository::class)->set(
                        'ep.root_user_id',
                        $user->getKey(),
                    );

                    return $user;
                },
            ],
        ]);
    }
}
