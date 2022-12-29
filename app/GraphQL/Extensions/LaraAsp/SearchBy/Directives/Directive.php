<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Directives;

use App\GraphQL\Extensions\LaraAsp\Builder\Traits\HandlerExtender;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;

class Directive extends SearchByDirective {
    use HandlerExtender;
}
