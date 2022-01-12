<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\Services\Auth\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class Root extends AuthDirective {
    public function __construct(
        protected Auth $auth,
    ) {
        parent::__construct();
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Authenticated user must be a root.
            """
            directive @root on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return $this->auth->isRoot($user);
    }
}
