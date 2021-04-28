<?php declare(strict_types = 1);

namespace Tests;

use App\CurrentTenant;
use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Routing\UrlGenerator;
use RuntimeException;

/**
 * @mixin \Tests\TestCase
 */
trait WithTenant {
    protected function setTenant(Organization|Closure|null $tenant): ?Organization {
        if ($tenant instanceof Closure) {
            $tenant = $tenant($this);
        }

        if ($tenant) {
            // Update bindings
            $this->app->bind(CurrentTenant::class, static function () use ($tenant): CurrentTenant {
                return (new CurrentTenant())->set($tenant);
            });

            // Update base url
            if (!$tenant->isRoot()) {
                $generator = $this->app->get(UrlGeneratorContract::class);

                if ($generator instanceof UrlGenerator) {
                    $generator->forceRootUrl("{$generator->formatScheme()}{$tenant->subdomain}.example.com");
                } else {
                    throw new RuntimeException('Impossible to set root url.');
                }
            }
        }

        return $tenant;
    }

    protected function useRootTenant(): ?Organization {
        return $this->setTenant(Organization::factory()->root()->create());
    }
}
