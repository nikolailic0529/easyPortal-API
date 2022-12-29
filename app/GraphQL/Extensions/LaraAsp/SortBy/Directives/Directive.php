<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SortBy\Directives;

use App\GraphQL\Extensions\LaraAsp\Builder\Traits\HandlerExtender;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;

class Directive extends SortByDirective {
    use HandlerExtender;
}
