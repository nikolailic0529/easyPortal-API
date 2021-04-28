<?php declare(strict_types = 1);

namespace App\Services\Tenant\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Services\Tenant\CurrentTenant;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;

/**
 * Determines current tenant based on properties of the current user.
 *
 * Important:
 * - must be added after {@link \Illuminate\Session\Middleware\AuthenticateSession}
 *   and {@link \Illuminate\Routing\Middleware\SubstituteBindings};
 * - must not be nested
 */
class SetTenant {
    protected Application $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function handle(Request $request, Closure $next): mixed {
        // Can be determined?
        $tenant = $this->getTenantFromRequest($request);

        // Process
        if ($tenant) {
            $this->app->bind(CurrentTenant::class, static function () use ($tenant): CurrentTenant {
                return (new CurrentTenant())->set($tenant);
            });
        }

        $result = $next($request);

        if ($tenant) {
            unset($this->app[CurrentTenant::class]);
        }

        return $result;
    }

    #[Pure]
    protected function getTenantFromRequest(Request $request): ?Organization {
        $user   = $request->user();
        $tenant = $user instanceof User
            ? $user->organization
            : null;

        return $tenant;
    }
}
