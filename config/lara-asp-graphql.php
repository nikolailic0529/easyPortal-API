<?php declare(strict_types = 1);

use App\GraphQL\Directives\Directives\Cached\CachedMode;
use App\GraphQL\Directives\Directives\Paginated\Trashed;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\Contains;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith;
use App\Models\Enums\OrganizationType;
use App\Services\Audit\Enums\Action;
use LastDragon_ru\LaraASP\Core\Enum as CoreEnum;
use LastDragon_ru\LaraASP\Eloquent\Enum as EloquentEnum;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators as SearchByOperators;
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
 *          operators: array<string, array<string|class-string<Operator>>>
 *      },
 *      sort_by: array{
 *          operators: array<string, array<string|class-string<Operator>>>
 *      },
 *      enums: array<class-string<CoreEnum>>
 *      } $settings
 */
$settings = [
    /**
     * Settings for {@see \LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective @searchBy} directive.
     */
    'search_by' => [
        /**
         * Operators
         * ---------------------------------------------------------------------
         *
         * You can (re)define types and supported operators here.
         *
         * @see Operator
         */
        'operators' => [
            'Date'                    => [
                SearchByOperators::Number,
            ],
            'DateTime'                => 'Date',
            'Url'                     => [
                SearchByOperators::String,
            ],
            'Email'                   => [
                SearchByOperators::String,
            ],
            'Color'                   => [
                SearchByOperators::String,
            ],
            'PhoneNumber'             => [
                SearchByOperators::String,
            ],
            'HtmlString'              => [
                SearchByOperators::String,
            ],
            SearchByOperators::String => [
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
     * Settings for {@see \LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective @sortBy} directive.
     */
    'sort_by'   => [
        /**
         * Operators
         * ---------------------------------------------------------------------
         *
         * You can (re)define types and supported operators here.
         *
         * @see Operator
         */
        'operators' => [
            // empty
        ],
    ],

    /**
     * These enums will be registered automatically. You can use key to specify
     * enum name.
     *
     * @deprecated Consider using native PHP enums.
     *
     * @see        CoreEnum
     * @see        EloquentEnum
     */
    'enums'     => [
        'Trashed'          => Trashed::class,
        'CachedMode'       => CachedMode::class,
        'AuditAction'      => Action::class,
        'OrganizationType' => OrganizationType::class,
    ],
];

return $settings;
