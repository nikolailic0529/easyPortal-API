<?php declare(strict_types = 1);

namespace App\Services\Auth0;

use Auth0\Login\Auth0Service;
use Auth0\SDK\Auth0;
use Auth0\SDK\Store\StoreInterface;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;

class AuthService {
    protected ?Auth0Service $service = null;

    public function __construct(
        public Container $container,
    ) {
        // empty
    }

    /**
     * @return array{profile: array<string, mixed>, accessToken: string}|null
     */
    public function signInByCode(string $code, string $state): ?array {
        // Auth0 operates $_GET, so we need to set them
        // @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
        $_GET['code']                     = $code;
        $_GET[Auth0::TRANSIENT_STATE_KEY] = $state;

        // @phpcs:enable

        return $this->getAuth()->getUser();
    }

    public function getSignInLink(): string {
        return $this->getAuth()
            ->login(null, null, [
                ['scope' => 'openid profile email'],
            ])
            ->getTargetUrl();
    }

    public function getAuth(): Auth0Service {
        if (!$this->service) {
            $this->service = new Auth0Service(
                $this->container->make(Repository::class)->get('laravel-auth0'),
                $this->container->make(StoreInterface::class),
                $this->container->make('cache.store'),
            );
        }

        return $this->service;
    }

    // <editor-fold desc="Auth0Service">
    // =========================================================================
    public function rememberUser(): bool {
        // TODO [Auth0] Not sure that this method needed, need to
        //      - check how "remember" work
        //      - get details about auth requirements
        return $this->getAuth()->rememberUser();
    }
    // </editor-fold>
}
