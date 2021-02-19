<?php declare(strict_types = 1);

namespace App\Services\Auth0;

use Auth0\Login\Auth0Service;
use Auth0\SDK\API\Authentication;
use Auth0\SDK\Auth0;
use Auth0\SDK\Store\StoreInterface;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use ReflectionProperty;

class AuthService {
    protected ?Auth0Service   $service = null;
    protected ?Authentication $auth    = null;
    protected ?Auth0          $sdk     = null;

    public function __construct(
        public Container $container,
    ) {
        // empty
    }

    /**
     * @return array{profile: array<string, mixed>, accessToken: string}|null
     */
    public function signInByPassword(string $username, string $password): ?array {
        $auth     = $this->getAuthentication();
        $token    = $auth->login([
            'username' => $username,
            'password' => $password,
            'realm'    => 'Username-Password-Authentication',
        ]);
        $userinfo = $auth->userinfo($token['access_token']);

        if ($userinfo) {
            $this->getSdk()->setUser($userinfo);
        }

        return $userinfo ? [
            'profile'     => $userinfo,
            'accessToken' => $token['access_token'],
        ] : null;
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

        return $this->getService()->getUser();
    }

    public function getSignInLink(): string {
        return $this->getService()
            ->login(null, null, [
                ['scope' => $this->getScope()],
            ])
            ->getTargetUrl();
    }

    public function getService(): Auth0Service {
        if (!$this->service) {
            // TODO Code the same as in Auth0\Login\LoginServiceProvider
            //      probably we need should not use Container
            $this->service = new Auth0Service(
                $this->container->make(Repository::class)->get('laravel-auth0'),
                $this->container->make(StoreInterface::class),
                $this->container->make('cache.store'),
            );
        }

        return $this->service;
    }

    protected function getAuthentication(): Authentication {
        if (!$this->auth) {
            $this->auth = $this->getPrivateProperty($this->getSdk(), 'authentication');
        }

        return $this->auth;
    }

    protected function getSdk(): Auth0 {
        if (!$this->sdk) {
            $this->sdk = $this->getPrivateProperty($this->getService(), 'auth0');
        }

        return $this->sdk;
    }

    protected function getScope(): string {
        return 'openid profile email';
    }

    private function getPrivateProperty(object $object, string $property): mixed {
        // TODO Unfortunately, there is no way to get SDK instance... So we use
        //      a dirty hack to get it. This allows us to avoid repeating a lot
        //      of code from Auth0Service that required for initialization.

        $property = new ReflectionProperty($object, $property);

        $property->setAccessible(true);

        return $property->getValue($object);
    }

    // <editor-fold desc="Auth0Service">
    // =========================================================================
    public function rememberUser(): bool {
        // TODO [Auth0] Not sure that this method needed, need to
        //      - check how "remember" work
        //      - get details about auth requirements
        return $this->getService()->rememberUser();
    }
    // </editor-fold>
}
