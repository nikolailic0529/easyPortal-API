<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\HasOrganization;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Only users from current organization is allowed.
 */
class Organization {
    public function __construct(
        protected Factory $auth,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string ...$guards): mixed {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (!$this->isAuthorized($this->auth->guard($guard)->user())) {
                throw new AuthorizationException();
            }
        }

        return $next($request);
    }

    protected function isAuthorized(Authenticatable|null $user): bool {
        return $user instanceof HasOrganization
            && $this->organization->defined()
            && $this->organization->is($user->getOrganization());
    }
}
