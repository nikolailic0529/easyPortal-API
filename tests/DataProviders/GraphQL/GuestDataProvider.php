<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLError;
use Tests\TestCase;

use function __;

/**
 * Only Guest cat perform the action.
 */
class GuestDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is allowed'    => [
                new Unknown(),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed' => [
                new ExpectedFinal(new GraphQLError($root, static function (): array {
                    return [__('errors.unauthenticated')];
                })),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
        ]);
    }
}
