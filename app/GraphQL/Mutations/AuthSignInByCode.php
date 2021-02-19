<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Me;
use App\Services\Auth0\AuthService;
use Auth0\Login\Contract\Auth0UserRepository;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthSignInByCode {
    protected Container           $container;
    protected AuthService         $service;
    protected Auth0UserRepository $repository;
    protected AuthManager         $auth;

    public function __construct(
        Container $container,
        AuthManager $auth,
        AuthService $service,
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
        // Get token from Auth0 and sign-in
        $info = $this->signIn($args);

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

    /**
     * @param array<string, array{code: string, state: string}> $args
     *
     * @return array{profile: array<string, mixed>, accessToken: string}|null
     */
    protected function signIn(array $args): ?array {
        return $this->service->signInByCode($args['code'], $args['state']);
    }
}
