<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\EndsWith as SearchByEndsWith;

class EndsWith extends SearchByEndsWith {
    use Fulltext;
}
