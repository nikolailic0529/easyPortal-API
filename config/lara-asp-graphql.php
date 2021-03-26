<?php declare(strict_types = 1);

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
            'Date'     => 'Int',
            'DateTime' => 'Date',
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
