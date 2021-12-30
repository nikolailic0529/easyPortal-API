<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use LengthException;

use function array_diff;
use function array_map;
use function implode;
use function is_array;
use function is_null;
use function json_encode;
use function sort;
use function sprintf;

abstract class Me extends AuthDirective {
    public function __construct(
        protected Gate $gate,
        protected Auth $auth,
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
            ) repeatable on FIELD_DEFINITION | OBJECT
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
            // Sort (to be consistent)
            sort($permissions);

            // Invalid?
            $invalid = $this->getInvalidPermissions($permissions);

            if ($invalid) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown permissions: `%s`',
                    implode('`, `', $invalid),
                ));
            }

            // Format
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

    /**
     * @param array<string> $permissions
     *
     * @return array<string>
     */
    protected function getInvalidPermissions(array $permissions): array {
        $available = $this->auth->getPermissions();
        $available = array_map(static function (Permission $permission): string {
            return $permission->getName();
        }, $available);
        $invalid   = array_diff($permissions, $available);

        return $invalid;
    }
}
