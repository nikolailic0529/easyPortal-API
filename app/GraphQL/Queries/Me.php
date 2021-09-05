<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

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
        return [
            'first_name'     => $user->given_name,
            'last_name'      => $user->family_name,
            'office_phone'   => $user->office_phone,
            'contact_email'  => $user->contact_email,
            'title'          => $user->title,
            'academic_title' => $user->academic_title,
            'mobile_phone'   => $user->mobile_phone,
            'department'     => $user->department,
            'job_title'      => $user->job_title,
            'phone'          => $user->phone,
            'company'        => $user->company,
            'photo'          => $user->photo,
        ];
    }
}
