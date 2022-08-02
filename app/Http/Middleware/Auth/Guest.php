<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Definitions\AuthGuestDirective;

class Guest extends Auth {
    public function __construct(AuthGuestDirective $directive) {
        parent::__construct($directive);
    }
}
