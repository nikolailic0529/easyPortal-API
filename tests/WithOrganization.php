<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization as OrganizationModel;
use App\Services\Organization\Organization;
use Closure;
use Illuminate\Contracts\Auth\Factory;

/**
 * @mixin \Tests\TestCase
 */
trait WithOrganization {
    protected function setOrganization(OrganizationModel|Closure|null $organization): ?OrganizationModel {
        if ($organization instanceof Closure) {
            $organization = $organization($this);
        }

        if ($organization) {
            $this->app->bind(Organization::class, function () use ($organization): Organization {
                return new class($this->app->make(Factory::class), $organization) extends Organization {
                    public function __construct(
                        Factory $auth,
                        protected OrganizationModel $organization,
                    ) {
                        parent::__construct($auth);
                    }

                    protected function getCurrent(): ?OrganizationModel {
                        return $this->organization;
                    }
                };
            });
        } else {
            unset($this->app[Organization::class]);
        }

        return $organization;
    }

    protected function useRootOrganization(): ?OrganizationModel {
        return $this->setOrganization(OrganizationModel::factory()->root()->create());
    }
}
