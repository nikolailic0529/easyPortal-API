<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Closure;
use Illuminate\Contracts\Config\Repository;

/**
 * @mixin TestCase
 *
 * @phpstan-type OrganizationFactory Organization|Closure(static):?Organization|null
 */
trait WithOrganization {
    /**
     * @param OrganizationFactory $organization
     */
    protected function setOrganization(Organization|Closure|null $organization): ?Organization {
        if ($organization instanceof Closure) {
            $organization = $organization($this);
        }

        if ($organization) {
            $this->app->bind(CurrentOrganization::class, function () use ($organization): CurrentOrganization {
                $root = $this->app->make(RootOrganization::class);
                $auth = $this->app->make(Auth::class);

                return new class($root, $auth, $organization) extends CurrentOrganization {
                    public function __construct(
                        RootOrganization $root,
                        Auth $auth,
                        protected Organization $organization,
                    ) {
                        parent::__construct($root, $auth);
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

    /**
     * @param OrganizationFactory $organization
     */
    public function setRootOrganization(Organization|Closure|null $organization): ?Organization {
        if ($organization instanceof Closure) {
            $organization = $organization($this);
        }

        if ($organization) {
            $this->app->bind(RootOrganization::class, function () use ($organization): RootOrganization {
                $config = $this->app->make(Repository::class);

                return new class($config, $organization) extends RootOrganization {
                    public function __construct(
                        Repository $config,
                        protected Organization $organization,
                    ) {
                        parent::__construct($config);
                    }

                    protected function getCurrent(): ?Organization {
                        return $this->organization;
                    }

                    protected function getRootKey(): ?string {
                        return $this->organization->getKey();
                    }
                };
            });
        } else {
            unset($this->app[RootOrganization::class]);
        }

        return $organization;
    }
}
