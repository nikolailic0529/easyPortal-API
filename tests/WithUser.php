<?php declare(strict_types = 1);

namespace Tests;

use App\Models\User;
use Closure;

/**
 * @mixin \Tests\TestCase
 */
trait WithUser {
    public function setUser(User|Closure|null $user): User|null {
        if ($user instanceof Closure) {
            $user = $user($this);
        }

        if ($user) {
            $this->actingAs($user);
        }

        return $user;
    }
}
