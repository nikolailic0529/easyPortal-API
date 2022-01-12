<?php declare(strict_types = 1);

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 */

use App\GraphQL\Directives\Directives\Cached\CachedMode;
use App\GraphQL\Directives\SearchBy\Operators\Comparison\Contains;
use App\GraphQL\Directives\SearchBy\Operators\Comparison\EndsWith;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Equal;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\In;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotEqual;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotIn;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\StartsWith;

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
            'Url'                           => SearchByDirective::ScalarString,
            'Email'                         => SearchByDirective::ScalarString,
            'Color'                         => SearchByDirective::ScalarString,
            SearchByDirective::ScalarString => [
                Equal::class,
                NotEqual::class,
                In::class,
                NotIn::class,
                Contains::class,
                EndsWith::class,
                StartsWith::class,
            ],
        ],
    ],

    /**
     * These enums will be registered automatically. You can use key to specify
     * enum name.
     *
     * @see \LastDragon_ru\LaraASP\Core\Enum
     * @see \LastDragon_ru\LaraASP\Eloquent\Enum
     */
    'enums'     => [
        CachedMode::class,
    ],
];
