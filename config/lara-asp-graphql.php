<?php declare(strict_types = 1);

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 */

use App\GraphQL\Directives\Directives\Cached\CachedMode;
use App\GraphQL\Directives\Directives\Paginated\Trashed;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\Contains;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith;
use App\Models\Enums\OrganizationType;
use LastDragon_ru\LaraASP\Core\Enum as CoreEnum;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Equal;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\In;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotEqual;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\NotIn;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\StartsWith;

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 *
 * @var array{
 *      search_by: array{
 *          operators: array<string, string|array<string|class-string<Operator>>>
 *      },
 *      enums: array<class-string<CoreEnum>>
 *      } $settings
 */
$settings = [
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
         * @see Operator
         */
        'scalars' => [
            'Date'                          => SearchByDirective::ScalarNumber,
            'DateTime'                      => 'Date',
            'Url'                           => SearchByDirective::ScalarString,
            'Email'                         => SearchByDirective::ScalarString,
            'Color'                         => SearchByDirective::ScalarString,
            'PhoneNumber'                   => SearchByDirective::ScalarString,
            'HtmlString'                    => SearchByDirective::ScalarString,
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
        Trashed::class,
        CachedMode::class,
        OrganizationType::class,
    ],
];

return $settings;
