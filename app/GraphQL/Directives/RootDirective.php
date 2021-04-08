<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use App\Models\User;
use Closure;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
use Nuwave\Lighthouse\Schema\AST\ASTHelper;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class RootDirective extends BaseDirective implements FieldMiddleware, TypeDefinitionNode {
    protected Factory    $auth;
    protected Repository $config;

    public function __construct(Factory $auth, Repository $config) {
        $this->auth   = $auth;
        $this->config = $config;
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Checks that current user is the root.
            """
            directive @root(
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
            $rootId        = $this->config->get('easyportal.root_user_id');
            $authorized    = false;
            $authenticated = false;

            foreach ($guards as $guard) {
                $guard = $this->auth->guard($guard);

                if ($guard && $guard->check()) {
                    $authenticated = true;
                    $user          = $guard->user();

                    if ($user instanceof User && $user->getKey() === $rootId) {
                        $authorized = true;
                        break;
                    }
                }
            }

            if (!$authenticated) {
                throw new AuthenticationException(
                    AuthenticationException::MESSAGE,
                    $guards,
                );
            } elseif (!$authorized) {
                throw new AuthorizationException();
            } else {
                // passed
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