<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\Services\Auth\Auth;
use Closure;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeExtensionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
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

use function sprintf;
use function str_contains;
use function trim;

abstract class AuthDirective extends BaseDirective implements
    FieldMiddleware,
    FieldManipulator,
    TypeManipulator,
    TypeExtensionManipulator {
    public function __construct(
        protected Auth $auth,
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
            if (!$this->allowed($root)) {
                throw $this->getAuthenticationException();
            }

            return $previous($root, $args, $context, $resolveInfo);
        };

        return $next($fieldValue->setResolver($resolver));
    }

    protected function allowed(mixed $root): bool {
        $user = $this->auth->getUser();

        if (!$this->isAuthenticated($user)) {
            throw $this->getAuthenticationException();
        } elseif (!$this->isAuthorized($user, $root)) {
            throw $this->getAuthorizationException();
        } else {
            // passed
        }

        return true;
    }

    protected function isAuthenticated(Authenticatable|null $user): bool {
        return (bool) $user;
    }

    abstract protected function isAuthorized(Authenticatable|null $user, mixed $root): bool;

    protected function getAuthenticationException(): AuthenticationException {
        return new AuthenticationException(
            AuthenticationException::MESSAGE,
        );
    }

    protected function getAuthorizationException(): AuthorizationException {
        throw new AuthorizationException();
    }
    // </editor-fold>

    // <editor-fold desc="Manipulate">
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
        $requirements = $this->getRequirements();

        if ($description && !str_contains($description, $tag)) {
            $description = trim($description);
        }

        foreach ($requirements as $requirement => $desc) {
            $requirement = "{$tag} {$requirement} {$desc}";

            if (!str_contains($description, $requirement)) {
                $description .= "\n\n{$requirement}";
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
    protected function getRequirements(): array {
        return [
            "<{$this->name()}>" => $this->getDefinitionNode()->description?->value,
        ];
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getDefinitionNode(): DirectiveDefinitionNode {
        return ASTHelper::extractDirectiveDefinition(static::definition());
    }

    protected function getArgDefinitionNode(
        DirectiveDefinitionNode $directive,
        string $argument,
    ): InputValueDefinitionNode {
        $definition = null;

        foreach ($directive->arguments as $node) {
            /** @var InputValueDefinitionNode $node */
            if ($node->name->value === $argument) {
                $definition = $node;
                break;
            }
        }

        if (!$definition) {
            throw new InvalidArgumentException(sprintf(
                'Argument `%s` does not exist.',
                $argument,
            ));
        }

        return $definition;
    }
    // </editor-fold>
}
