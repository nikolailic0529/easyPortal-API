<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Definitions\AuthRootDirective;

class Root extends Auth {
    public function __construct(AuthRootDirective $directive) {
        parent::__construct($directive);
    }
}
