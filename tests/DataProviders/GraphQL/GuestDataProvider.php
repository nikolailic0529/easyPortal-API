<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL;

use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLError;

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
                new ExpectedFinal(new GraphQLError($root, ['Unauthenticated.'])),
                static function (): ?User {
                    return User::factory()->make();
                },
            ],
        ]);
    }
}
