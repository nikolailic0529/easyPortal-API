<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Only guests allowed.
 */
class Guest {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string ...$guards): mixed {
        $guards = $guards ?: [null];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                throw new AccessDeniedHttpException();
            }
        }

        return $next($request);
    }
}
