<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

use function is_callable;

/**
 * @mixin TestCase
 *
 * @phpstan-type OrganizationFactory Organization|callable(\Tests\TestCase):?Organization|null
 */
trait WithOrganization {
    /**
     * @template T of Organization|null
     *
     * @param T|callable(TestCase):T $organization
     *
     * @return   (T is null ? null : Organization)
     */
    protected function setOrganization(Organization|callable|null $organization): ?Organization {
        if (is_callable($organization)) {
            $organization = $organization($this);
        }

        if ($organization instanceof Organization) {
            $this->app->bind(WithOrganizationToken::class, static function () use ($organization): Organization {
                return $organization;
            });
            $this->app->bind(CurrentOrganization::class, function (): CurrentOrganization {
                $root       = $this->app->make(RootOrganization::class);
                $auth       = $this->app->make(Auth::class);
                $container  = $this->app;
                $dispatcher = $this->app->make(Dispatcher::class);

                return new class($dispatcher, $root, $auth, $container) extends CurrentOrganization {
                    public function __construct(
                        Dispatcher $dispatcher,
                        RootOrganization $root,
                        Auth $auth,
                        protected Container $container,
                    ) {
                        parent::__construct($dispatcher, $root, $auth);
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

            $organization = null;
        }

        return $organization;
    }

    /**
     * @template T of Organization|null
     *
     * @param T|callable(TestCase):T $organization
     *
     * @return   (T is null ? null : Organization)
     */
    public function setRootOrganization(Organization|callable|null $organization): ?Organization {
        if (is_callable($organization)) {
            $organization = $organization($this);
        }

        if ($organization instanceof Organization) {
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

            $organization = null;
        }

        return $organization;
    }
}
