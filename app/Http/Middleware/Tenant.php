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
use function filter_var;
use function in_array;
use function parse_url;
use function reset;
use function str_starts_with;

use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_IP;
use const PHP_URL_HOST;

/**
 * Determine current tenant based on domain name and host, return `404` if not
 * possible.
 *
 * Important:
 * - must be added before {@link \Illuminate\Session\Middleware\AuthenticateSession}
 *   and {@link \Illuminate\Routing\Middleware\SubstituteBindings};
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
        $this->app->bind(CurrentTenant::class, static function () use ($tenant): CurrentTenant {
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

        if ($this->isRootDomain($server)) {
            $name = Organization::ROOT;
        } else {
            $name = $this->getTenantNameFromDomain($server);

            if (!$name && $this->isWildcardDomain($server)) {
                // Host header is not safe, so we use it only if domain specified as
                // wildcard, eg `*.example.com`
                $host = (string) $request->server->get('HTTP_HOST');
                $name = $this->getTenantNameFromDomain($host);
            }
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
        $sub  = reset($subs);

        if (count($subs) === 3 && $sub !== '*' && $sub !== 'www') {
            $name = $sub;
        }

        return $name;
    }

    #[Pure]
    protected function isWildcardDomain(string $domain): bool {
        return (($this->app->isLocal() || $this->app->runningUnitTests()) && in_array($domain, ['_'], true))
            || (str_starts_with($domain, '*.') && count(explode('.', $domain)) === 3);
    }

    #[Pure]
    protected function isRootDomain(string $domain): bool {
        return empty($domain)
            || filter_var(parse_url("http://{$domain}/", PHP_URL_HOST), FILTER_VALIDATE_IP)
            || in_array($domain, ['_'], true)
            || count(explode('.', $domain)) === 2;
    }
}
