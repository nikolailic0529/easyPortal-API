<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Definitions\AuthMeDirective;

class Me extends Auth {
    public function __construct(AuthMeDirective $directive) {
        parent::__construct($directive);
    }
}
