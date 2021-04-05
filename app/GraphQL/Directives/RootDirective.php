<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use Closure;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;
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
            Checks that current user is the root user.
            """
            directive @root(
                """
                Applying root check to the underlying query
                """
                scopes: [String!]
            ) on FIELD_DEFINITION
            GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
        $previous = $fieldValue->getResolver();
        $resolver = function (
            $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo,
        ) use ($previous): mixed {
            $webGuard = $this->auth->guard('web');
            $rootId   = $this->config->get('easyportal.root_user_id');
            if (!$webGuard->check()) {
                throw new AuthenticationException(
                    AuthenticationException::MESSAGE,
                    ['web'],
                );
            }
            if (!$rootId || $rootId !== $webGuard->user()->id) {
                // if user is root or root doesn't exists
                throw new AuthorizationException('Authorization failed');
            }

            return $previous($root, $args, $context, $resolveInfo);
        };

        $fieldValue->setResolver($resolver);

        return $next($fieldValue);
    }
}
