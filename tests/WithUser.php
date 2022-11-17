<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;
use App\Models\User;

use function is_callable;

/**
 * @mixin TestCase
 *
 * @phpstan-type UserFactory User|callable(\Tests\TestCase, ?Organization):?User|null
 */
trait WithUser {
    /**
     * @template T of User|null
     * @template C of Organization|null
     *
     * @param T|callable(TestCase, C):T $user
     * @param C                        $organization
     *
     * @return (T is null ? null : User)
     */
    public function setUser(User|callable|null $user, Organization $organization = null): User|null {
        if (is_callable($user)) {
            $user = $user($this, $organization);
        }

        if ($user instanceof User) {
            $this->actingAs($user);
        } else {
            $user = null;
        }

        return $user;
    }
}
