<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Definitions;

use App\GraphQL\Directives\Directives\Rules\NotMe;

class IsNotMe extends NotMe {
    // Lighthouse loads all files from the directives directory...
}
