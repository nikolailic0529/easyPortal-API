<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Routing\UrlGenerator;
use RuntimeException;

/**
 * @mixin \Tests\TestCase
 */
trait WithTenant {
    public function setTenant(Organization|Closure|null $tenant): ?Organization {
        if ($tenant instanceof Closure) {
            $tenant = $tenant($this);
        }

        if ($tenant && !$tenant->isRoot()) {
            $generator = $this->app->get(UrlGeneratorContract::class);

            if ($generator instanceof UrlGenerator) {
                $generator->forceRootUrl("{$generator->formatScheme()}{$tenant->subdomain}.example.com");
            } else {
                throw new RuntimeException('Impossible to set root url.');
            }
        }

        return $tenant;
    }
}
