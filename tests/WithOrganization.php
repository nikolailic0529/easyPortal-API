<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

use function is_callable;

/**
 * @mixin TestCase
 *
 * @phpstan-type OrganizationFactory Organization|callable(\Tests\TestCase):?Organization|null
 */
trait WithOrganization {
    /**
     * @param OrganizationFactory $organization
     */
    protected function setOrganization(Organization|callable|null $organization): ?Organization {
        if (is_callable($organization)) {
            $organization = $organization($this);
        }

        if ($organization) {
            $this->app->bind(WithOrganizationToken::class, static function () use ($organization): Organization {
                return $organization;
            });
            $this->app->bind(CurrentOrganization::class, function (): CurrentOrganization {
                $root      = $this->app->make(RootOrganization::class);
                $auth      = $this->app->make(Auth::class);
                $container = $this->app;

                return new class($root, $auth, $container) extends CurrentOrganization {
                    public function __construct(
                        RootOrganization $root,
                        Auth $auth,
                        protected Container $container,
                    ) {
                        parent::__construct($root, $auth);
                    }

                    protected function getCurrent(): ?Organization {
                        return $this->container->get(WithOrganizationToken::class);
                    }

                    public function set(Organization $organization): bool {
                        $result = parent::set($organization);

                        if ($result) {
                            $this->container->bind(
                                WithOrganizationToken::class,
                                static function () use ($organization): Organization {
                                    return $organization;
                                },
                            );
                        }

                        return $result;
                    }
                };
            });
        } else {
            unset($this->app[CurrentOrganization::class]);
            unset($this->app[WithOrganizationToken::class]);
        }

        return $organization;
    }

    /**
     * @param OrganizationFactory $organization
     */
    public function setRootOrganization(Organization|callable|null $organization): ?Organization {
        if (is_callable($organization)) {
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
