<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Me;
use Auth0\Login\Auth0Service;
use Auth0\Login\Contract\Auth0UserRepository;
use Auth0\SDK\Auth0;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthSignIn {
    protected Container           $container;
    protected Auth0Service        $service;
    protected Auth0UserRepository $repository;
    protected AuthManager         $auth;

    public function __construct(
        Container $container,
        AuthManager $auth,
        Auth0Service $service,
        Auth0UserRepository $repository,
    ) {
        $this->container  = $container;
        $this->auth       = $auth;
        $this->service    = $service;
        $this->repository = $repository;
    }

    /**
     * @param array<string, array{code: string, state: string}> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args): ?array {
        // Auth0 operates $_GET, so we need to set them
        // @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
        $_GET['code']                     = $args['code'];
        $_GET[Auth0::TRANSIENT_STATE_KEY] = $args['state'];
        // @phpcs:enable

        // Get token from Auth0 and sign-in
        $info = $this->service->getUser();

        if ($info) {
            try {
                $user = $this->repository->getUserByUserInfo($info);
            } catch (ModelNotFoundException) {
                $user = null;
            }

            if ($user) {
                $this->auth->login($user, $this->service->rememberUser());
            }
        } else {
            // TODO Should we throw error here?
        }

        // Get Current User
        $me = $this->container->make(Me::class)(null, []);

        return $me;
    }
}
