<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use App\CurrentTenant;
use App\Models\Organization;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function count;
use function explode;
use function in_array;
use function reset;
use function str_starts_with;

/**
 * Determine current tenant based on domain name and host, return `404` if not
 * possible.
 *
 * Important:
 * - must be added before {@link \Illuminate\Routing\Middleware\SubstituteBindings};
 * - must not be nested
 */
class Tenant {
    protected Application $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function handle(Request $request, Closure $next): mixed {
        // Can be determined?
        $tenant = $this->getTenantFromRequest($request);

        if (!$tenant) {
            throw new NotFoundHttpException();
        }

        // Process
        $this->app->bind(CurrentTenant::class, function () use ($tenant) {
            return (new CurrentTenant())->set($tenant);
        });

        $result = $next($request);

        unset($this->app[CurrentTenant::class]);

        return $result;
    }

    #[Pure]
    protected function getTenantFromRequest(Request $request): ?Organization {
        // TODO [core] Test for Apache (?)

        // Determine tenant name
        $server = (string) $request->server->get('SERVER_NAME');
        $name   = $this->getTenantNameFromDomain($server);

        if (!$name && $this->isWildcardDomain($server)) {
            // Host header is not safe, so we use it only if domain specified as
            // wildcard, eg `*.example.com`
            $host = (string) $request->server->get('HTTP_HOST');
            $name = $this->getTenantNameFromDomain($host);
        }

        // Search for model
        $tenant = null;

        if ($name) {
            $tenant = Organization::query()
                ->where('subdomain', '=', $name)
                ->first();
        }

        return $tenant;
    }

    #[Pure]
    protected function getTenantNameFromDomain(string $domain): ?string {
        $name = null;
        $subs = explode('.', $domain);
        $last = reset($subs);

        if (count($subs) === 3 && $last !== '*') {
            $name = $last;
        }

        return $name;
    }

    #[Pure]
    protected function isWildcardDomain(string $domain): bool {
        return ($this->app->isLocal() && in_array($domain, ['_'], true))
            || (str_starts_with($domain, '*.') && count(explode('.', $domain)) === 3);
    }
}
