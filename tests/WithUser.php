<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Models\User;

use function is_callable;

/**
 * @mixin TestCase
 *
 * @phpstan-type UserFactory User|callable(static, ?Organization=):?User|null
 */
trait WithUser {
    /**
     * @param UserFactory $user
     */
    public function setUser(User|callable|null $user, Organization $organization = null): User|null {
        if (is_callable($user)) {
            $user = $user($this, $organization);
        }

        if ($user) {
            $this->actingAs($user);
        }

        return $user;
    }
}
