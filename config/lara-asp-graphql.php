<?php declare(strict_types = 1);

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 */

use App\GraphQL\Directives\SearchBy\Operators\Contains;
use App\GraphQL\Directives\SearchBy\Operators\EndsWith;
use App\GraphQL\Directives\SearchBy\Operators\StartsWith;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Equal;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\In;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotEqual;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotIn;

return [
    /**
     * Settings for @searchBy directive.
     */
    'search_by' => [
        /**
         * Scalars
         * ---------------------------------------------------------------------
         *
         * You can (re)define scalars and supported operators here.
         *
         * @var array<string, array<string|\LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\Operator>>
         */
        'scalars' => [
            'Date'                          => 'Int',
            'DateTime'                      => 'Date',
            SearchByDirective::ScalarString => [
                Equal::class,
                NotEqual::class,
                In::class,
                NotIn::class,
                Contains::class,
                StartsWith::class,
                EndsWith::class,
            ],
        ],
    ],
];
