<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Directives\AuthDirective;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use LengthException;

use function is_array;
use function is_null;
use function json_encode;
use function sort;

abstract class Me extends AuthDirective {
    public function __construct(
        protected Gate $gate,
    ) {
        parent::__construct();
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            User must be authenticated.
            """
            directive @me(
                """
                User must be authenticated and have any of these permissions.
                """
                permissions: [String!]
            ) on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(?Authenticatable $user): bool {
        $permissions = $this->getPermissions();
        $authorized  = is_null($permissions);

        if ($permissions) {
            $authorized = $this->gate->forUser($user)->any($permissions);
        }

        return $authorized;
    }

    /**
     * @inheritDoc
     */
    protected function getRequirements(): array {
        $permissions  = $this->getPermissions();
        $requirements = [];

        if ($permissions) {
            sort($permissions);

            $permissions = json_encode($permissions);
            $definition  = $this->getDefinitionNode();
            $argument    = $this->getArgDefinitionNode($definition, 'permissions');

            $requirements = [
                "<{$this->name()}({$permissions})>" => $argument->description?->value,
            ];
        } else {
            $requirements = parent::getRequirements();
        }

        return $requirements;
    }

    /**
     * @return array<string>|null
     */
    protected function getPermissions(): ?array {
        $permissions = $this->directiveArgValue('permissions');

        if (is_array($permissions) && !$permissions) {
            throw new LengthException('Permissions cannot be empty.');
        }

        return $permissions;
    }
}
