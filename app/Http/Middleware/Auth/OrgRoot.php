<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\GraphQL\Directives\Definitions\AuthOrgRootDirective;

class OrgRoot extends Auth {
    public function __construct(AuthOrgRootDirective $directive) {
        parent::__construct($directive);
    }
}
