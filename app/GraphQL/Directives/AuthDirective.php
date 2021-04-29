<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use Closure;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeExtensionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\TypeExtensionManipulator;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

use function array_unique;
use function str_contains;
use function trim;

abstract class AuthDirective extends BaseDirective implements
    FieldMiddleware,
    FieldManipulator,
    TypeManipulator,
    TypeExtensionManipulator {

    public function __construct(
        protected Factory $auth,
        protected Repository $config,
    ) {
        // empty
    }

    // <editor-fold desc="Auth">
    // =========================================================================
    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
        $previous = $fieldValue->getResolver();
        $resolver = function (
            $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo,
        ) use (
            $previous,
        ): mixed {
            if (!$this->allowed()) {
                throw $this->getAuthenticationException();
            }

            return $previous($root, $args, $context, $resolveInfo);
        };

        return $next($fieldValue->setResolver($resolver));
    }

    /**
     * @return array<string>
     */
    protected function getGuards(): array {
        $guards = (array) $this->directiveArgValue('with');
        $guards = $guards ?: [$this->config->get('lighthouse.guard')];
        $guards = array_unique($guards);

        return $guards;
    }

    protected function allowed(): bool {
        $guards        = $this->getGuards();
        $authorized    = false;
        $authenticated = false;

        foreach ($guards as $name) {
            $guard = $this->auth->guard($name);

            if ($guard && $this->isAuthenticated($guard)) {
                $authenticated = true;
                $authorized    = $this->isAuthorized($guard->user());

                $this->auth->shouldUse($name);

                break;
            }
        }

        if (!$authenticated) {
            throw $this->getAuthenticationException();
        } elseif (!$authorized) {
            throw $this->getAuthorizationException();
        } else {
            // passed
        }

        return true;
    }

    protected function isAuthenticated(Guard $guard): bool {
        return $guard->check();
    }

    abstract protected function isAuthorized(Authenticatable|null $user): bool;

    protected function getAuthenticationException(): AuthenticationException {
        return new AuthenticationException(
            AuthenticationException::MESSAGE,
            $this->getGuards(),
        );
    }

    protected function getAuthorizationException(): AuthorizationException {
        throw new AuthorizationException();
    }
    // </editor-fold>

    // <editor-fold desc="Description">
    // =========================================================================
    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $this->addRequirements($fieldDefinition);
    }

    public function manipulateTypeDefinition(DocumentAST &$documentAST, TypeDefinitionNode &$typeDefinition): void {
        ASTHelper::addDirectiveToFields($this->directiveNode, $typeDefinition);

        $this->addRequirements($typeDefinition);
    }

    public function manipulateTypeExtension(DocumentAST &$documentAST, TypeExtensionNode &$typeExtension): void {
        ASTHelper::addDirectiveToFields($this->directiveNode, $typeExtension);

        $this->addRequirements($typeExtension);
    }

    protected function addRequirements(FieldDefinitionNode|TypeDefinitionNode|TypeExtensionNode $node): void {
        // Supported?
        if (
            !($node instanceof ObjectTypeDefinitionNode)
            && !($node instanceof ObjectTypeExtensionNode)
            && !($node instanceof FieldDefinitionNode)
        ) {
            return;
        }

        // Generate
        $tag          = '@require';
        $description  = $node->description->value ?? '';
        $requirements = array_unique($this->requirements());

        if ($description && !str_contains($description, $tag)) {
            $description = trim($description)."\n\n";
        }

        foreach ($requirements as $requirement) {
            $requirement = "{$tag} {$requirement}";

            if (!str_contains($description, $requirement)) {
                $description .= "{$requirement}\n";
            }
        }

        // Set
        $node->description = Parser::description(
            <<<GRAPHQL
            """
            {$description}
            """
            GRAPHQL,
        );
    }

    /**
     * @return array<string>
     */
    protected function requirements(): array {
        return ["<{$this->directiveNode->name->value}>"];
    }
    // </editor-fold>
}
