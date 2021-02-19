<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use Closure;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\TypeExtensionManipulator;
use Nuwave\Lighthouse\Support\Contracts\TypeManipulator;

class GuestDirective extends BaseDirective implements FieldMiddleware, TypeManipulator, TypeExtensionManipulator {
    protected Factory    $auth;
    protected Repository $config;

    public function __construct(Factory $auth, Repository $config) {
        $this->auth   = $auth;
        $this->config = $config;
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Checks that current visitor is guest.
            """
            directive @guest(
                """
                Specify which guards to use, e.g. ["api"].
                When not defined, the default from `lighthouse.php` is used.
                """
                with: [String!]
            ) on FIELD_DEFINITION | OBJECT
            GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
        $guards   = (array) $this->directiveArgValue('with');
        $guards   = $guards ?: [$this->config->get('lighthouse.guard')];
        $previous = $fieldValue->getResolver();
        $resolver = function (
            $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo,
        ) use (
            $guards,
            $previous,
        ): mixed {
            foreach ($guards as $guard) {
                if ($this->auth->guard($guard)->check()) {
                    throw new AuthenticationException(
                        AuthenticationException::MESSAGE,
                        $guards,
                    );
                }
            }

            return $previous($root, $args, $context, $resolveInfo);
        };

        $fieldValue->setResolver($resolver);

        return $next($fieldValue);
    }

    public function manipulateTypeDefinition(DocumentAST &$documentAST, TypeDefinitionNode &$typeDefinition): void {
        ASTHelper::addDirectiveToFields($this->directiveNode, $typeDefinition);
    }

    public function manipulateTypeExtension(DocumentAST &$documentAST, TypeExtensionNode &$typeExtension): void {
        ASTHelper::addDirectiveToFields($this->directiveNode, $typeExtension);
    }
}
