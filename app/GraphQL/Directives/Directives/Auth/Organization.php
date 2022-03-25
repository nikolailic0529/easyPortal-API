<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\HasOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

abstract class Organization extends AuthDirective implements FieldMiddleware {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        parent::__construct();
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Authenticated user must be a member of the current organization.
            """
            directive @authOrganization(
              """
              Authenticated user must be a member of the root organization.
              """
              root: Boolean! = false
            ) on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    protected function isAuthorized(Authenticatable|null $user, mixed $root): bool {
        return $user instanceof HasOrganization
            && $this->organization->defined()
            && $this->organization->is($user->getOrganization())
            && (!$this->isRootRequired() || $this->organization->isRoot());
    }

    /**
     * @inheritDoc
     */
    protected function getRequirements(): array {
        $root         = $this->isRootRequired();
        $requirements = [];

        if ($root) {
            $definition = $this->getDefinitionNode();
            $argument   = $this->getArgDefinitionNode($definition, 'root');

            $requirements = [
                "<{$this->name()}(root)>" => $argument->description?->value,
            ];
        } else {
            $requirements = parent::getRequirements();
        }

        return $requirements;
    }

    protected function isRootRequired(): bool {
        return (bool) $this->directiveArgValue('root');
    }
}
