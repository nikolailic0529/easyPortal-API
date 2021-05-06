<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;

use function in_array;

abstract class Root extends AuthDirective {
    public function __construct(
        protected Repository $config,
    ) {
        parent::__construct();
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Current user must be a root.
            """
            directive @root on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user): bool {
        return $this->isRoot($user);
    }

    public function isRoot(Authenticatable|null $user): bool {
        return $user
            && in_array($user->getAuthIdentifier(), (array) $this->config->get('ep.root_users'), true);
    }
}
