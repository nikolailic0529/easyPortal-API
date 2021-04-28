<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Tenant\OrganizationTenant;
use App\Services\Tenant\Tenant;
use Closure;

/**
 * @mixin \Tests\TestCase
 */
trait WithTenant {
    protected function setTenant(Organization|Closure|null $tenant): ?Organization {
        if ($tenant instanceof Closure) {
            $tenant = $tenant($this);
        }

        if ($tenant) {
            $this->app->bind(Tenant::class, static function () use ($tenant): Tenant {
                return new OrganizationTenant($tenant);
            });
        } else {
            unset($this->app[Tenant::class]);
        }

        return $tenant;
    }

    protected function useRootTenant(): ?Organization {
        return $this->setTenant(Organization::factory()->root()->create());
    }
}
