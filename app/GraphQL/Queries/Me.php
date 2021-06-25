<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_key_exists;

class Me {
    public function __construct(
        protected Auth $auth,
        protected Client $client,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<mixed>|null
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): ?User {
        return $this->getMe($context->user());
    }

    public function root(?User $user): bool {
        return $this->auth->isRoot($user);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getMe(?Authenticatable $user): ?User {
        $me = null;

        if ($user instanceof User) {
            $me = $user;
        } elseif ($user) {
            $me                      = new User();
            $me->{$me->getKeyName()} = $user->getAuthIdentifier();
        } else {
            $me = null;
        }

        return $me;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function profile(?User $user): ?array {
        if (!$user) {
            return null;
        }

        $keycloakUser = $this->client->getUserById($user->getKey());
        $attributes   = $keycloakUser->attributes;
        $keys         = [
            'office_phone',
            'contact_email',
            'title',
            'academic_title',
            'office_phone',
            'mobile_phone',
            'contact_email',
            'department',
            'job_title',
            'photo',
        ];
        $data         = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes)) {
                $data[$key] = $attributes[$key][0];
            }
        }
        $data['first_name'] = $keycloakUser->firstName ?? null;
        $data['last_name']  = $keycloakUser->lastName ?? null;
        return $data;
    }
}
