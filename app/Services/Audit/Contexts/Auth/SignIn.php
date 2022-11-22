<?php declare(strict_types = 1);

namespace App\Services\Audit\Contexts\Auth;

use App\Services\Audit\Contexts\Context;

class SignIn extends Context {
    public function __construct(
        public string $guard,
        public bool $remember,
    ) {
        // empty
    }
}
