<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SortBy\Types;

use App\GraphQL\Extensions\LaraAsp\Builder\Traits\InputObjectExtender;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Types\Clause as SortByClause;

class Clause extends SortByClause {
    use InputObjectExtender;
}
