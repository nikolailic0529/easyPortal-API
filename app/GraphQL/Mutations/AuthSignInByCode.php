<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Me;
use App\Models\User;
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
    public function __invoke(mixed $_, array $args): ?User {
        // Get token from Auth0 and sign-in
        $info   = $this->signIn($args);
        $signed = false;

        if ($info) {
            try {
                // Get user
                /** @var \App\Models\User $user */
                $user = $this->repository->getUserByUserInfo($info);

                // Update user
                // Blocked user cannon sign-in
                if ($info['profile']['email_verified'] ?? false) {
                    $user->markEmailAsVerified();
                } else {
                    // FIXME [!][Auth0] i18n + Better error handling
                    throw new ModelNotFoundException('User is unverified.');
                }

                $user->blocked     = false;
                $user->given_name  = $info['profile']['given_name'];
                $user->family_name = $info['profile']['family_name'];
                $user->photo       = $info['profile']['picture'];

                $user->save();

                // Sign-In
                $this->auth->login($user, $this->service->rememberUser());

                // Mark
                $signed = true;
            } catch (ModelNotFoundException) {
                // empty
            }
        } else {
            // TODO Should we throw error here?
        }

        // Auth0 may store the user inside the session (somewhere in \Auth0\SDK\Auth0)
        // so we need to delete it if sign-in failed.
        if (!$signed) {
            $this->auth->logout();
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
