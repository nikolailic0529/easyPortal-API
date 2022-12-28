<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Types;

use App\GraphQL\Extensions\LaraAsp\Builder\Traits\InputObjectExtender;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Types\Condition as SearchByCondition;

class Condition extends SearchByCondition {
    use InputObjectExtender;
}
