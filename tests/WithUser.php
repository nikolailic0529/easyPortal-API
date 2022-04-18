<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use Closure;

/**
 * @mixin TestCase
 *
 * @phpstan-type UserFactory User|Closure(static, ?Organization=):?User|null
 */
trait WithUser {
    /**
     * @param UserFactory $user
     */
    public function setUser(User|Closure|null $user, Organization $organization = null): User|null {
        if ($user instanceof Closure) {
            $user = $user($this, $organization);
        }

        if ($user) {
            $this->actingAs($user);
        }

        return $user;
    }
}
