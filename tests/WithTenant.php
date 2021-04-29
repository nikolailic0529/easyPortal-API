<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Tenant\Tenant;
use Closure;
use Illuminate\Contracts\Auth\Factory;

/**
 * @mixin \Tests\TestCase
 */
trait WithTenant {
    protected function setTenant(Organization|Closure|null $tenant): ?Organization {
        if ($tenant instanceof Closure) {
            $tenant = $tenant($this);
        }

        if ($tenant) {
            $this->app->bind(Tenant::class, function () use ($tenant): Tenant {
                return new class($this->app->make(Factory::class), $tenant) extends Tenant {
                    public function __construct(
                        Factory $auth,
                        protected Organization $organization,
                    ) {
                        parent::__construct($auth);
                    }

                    protected function getCurrent(): ?Organization {
                        return $this->organization;
                    }
                };
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
