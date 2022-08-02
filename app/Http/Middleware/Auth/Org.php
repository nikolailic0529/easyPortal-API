<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Definitions\AuthOrgDirective;

class Org extends Auth {
    public function __construct(AuthOrgDirective $directive) {
        parent::__construct($directive);
    }
}
