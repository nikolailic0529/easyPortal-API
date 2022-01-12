<?php declare(strict_types = 1);

namespace App\Policies;

use App\Models\User;
use App\Services\Auth\Auth;

class UserPolicy {
    public function __construct(
        protected Auth $auth,
    ) {
        // empty
    }

    public function administer(User $me, ?User $user): bool {
        return !$this->auth->isRoot($user) || $this->auth->isRoot($me);
    }

    public function orgAdminister(User $me, ?User $user): bool {
        return $this->administer($me, $user);
    }
}
