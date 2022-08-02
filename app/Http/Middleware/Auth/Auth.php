<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Directives\Auth\AuthDirective;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Exceptions\AuthenticationException as GraphQLAuthenticationException;
use Nuwave\Lighthouse\Exceptions\AuthorizationException as GraphQLAuthorizationException;

abstract class Auth {
    protected function __construct(
        protected AuthDirective $directive,
    ) {
        // empty
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next, mixed $root = null): mixed {
        try {
            if (!$this->directive->isAllowed($root)) {
                throw new AuthenticationException();
            }
        } catch (GraphQLAuthenticationException $exception) {
            throw new AuthenticationException($exception->getMessage());
        } catch (GraphQLAuthorizationException $exception) {
            throw new AuthorizationException($exception->getMessage());
        }

        return $next($request);
    }
}
