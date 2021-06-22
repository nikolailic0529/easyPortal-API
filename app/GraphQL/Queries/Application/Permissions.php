<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Auth\Auth;

class Permissions {
    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return array<string>
     */
    public function __invoke($_, array $args): array {
        return $this->auth->getPermissions();
    }
}
