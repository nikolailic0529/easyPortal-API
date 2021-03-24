<?php declare(strict_types = 1);

use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Between;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Equal;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\GreaterThan;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\GreaterThanOrEqual;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\LessThan;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\LessThanOrEqual;
/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 */

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
            'Date' => [
                Equal::class,
                LessThan::class,
                LessThanOrEqual::class,
                GreaterThan::class,
                GreaterThanOrEqual::class,
                Between::class,
            ]
        ],

        /**
         * Scalar aliases
         * ---------------------------------------------------------------------
         *
         * Allow redefine scalar type in conditions.
         */
        'aliases' => [
            // empty
        ],
    ],
];
