<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Organization\CurrentOrganization;
use Closure;
use Illuminate\Contracts\Auth\Factory;

/**
 * @mixin \Tests\TestCase
 */
trait WithOrganization {
    protected function setOrganization(Organization|Closure|null $organization): ?Organization {
        if ($organization instanceof Closure) {
            $organization = $organization($this);
        }

        if ($organization) {
            $this->app->bind(CurrentOrganization::class, function () use ($organization): CurrentOrganization {
                return new class($this->app->make(Factory::class), $organization) extends CurrentOrganization {
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
            unset($this->app[CurrentOrganization::class]);
        }

        return $organization;
    }

    protected function useRootOrganization(): ?Organization {
        return $this->setOrganization(Organization::factory()->root()->create());
    }
}
