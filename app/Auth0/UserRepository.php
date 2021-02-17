<?php declare(strict_types = 1);

namespace App\Auth0;

use App\Models\User;
use Auth0\Login\Auth0JWTUser;
use Auth0\Login\Contract\Auth0UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function is_null;

class UserRepository implements Auth0UserRepository {
    protected Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * @param array<string, mixed> $decodedJwt
     */
    public function getUserByDecodedJWT(array $decodedJwt): Authenticatable {
        return new Auth0JWTUser($decodedJwt);
    }

    /**
     * @param array{profile: array<mixed>, accessToken: string|null} $userInfo
     */
    public function getUserByUserInfo(array $userInfo): User {
        // TODO [Auth0] Should we create a user if it does not exist?
        $user = $this->getUser($userInfo['profile']['sub'] ?? null);

        if (is_null($user)) {
            throw (new ModelNotFoundException())->setModel(User::class);
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function getUserByIdentifier($identifier): ?User {
        $info = $this->getAuth0UserInfo();
        $user = null;

        if ($info) {
            try {
                $user = $this->getUserByUserInfo($info);
            } catch (ModelNotFoundException) {
                // no action
            }

            if ($user && $user->getAuthIdentifier() !== $identifier) {
                $user = null;
            }
        }

        return $user;
    }

    protected function getUser(?string $id): ?User {
        $table = new User();
        $user  = User::query()
            ->where($table->getAuthIdentifierName(), '=', $id)
            ->first();

        return $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getAuth0UserInfo(): ?array {
        return  $this->container->make('auth0')->getUser();
    }
}
