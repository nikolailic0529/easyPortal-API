<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\GraphQL\ErrorFormatter;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Directives\Directive as SearchBy;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\Contains as ContainsOperator;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith as EndsWithOperator;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex\Relation as RelationOperator;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Types\Condition as ConditionType;
use App\GraphQL\Extensions\LaraAsp\SortBy\Directives\Directive as SortBy;
use App\GraphQL\Extensions\LaraAsp\SortBy\Types\Clause as ClauseType;
use App\GraphQL\Extensions\Lighthouse\DirectiveLocator;
use App\GraphQL\Extensions\Lighthouse\Directives\EqDirective;
use App\GraphQL\Extensions\Lighthouse\Directives\ValidatorDirective;
use App\GraphQL\Listeners\CacheExpiredListener;
use App\GraphQL\Providers\ValidationRulesProvider;
use App\Utils\Providers\EventsProvider;
use App\Utils\Providers\ServiceServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByOperatorContainsDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByOperatorEndsWithDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByOperatorRelationDirective;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Types\Condition as SearchByTypeCondition;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Types\Clause as SortByClause;
use Nuwave\Lighthouse\Events\ManipulateResult;
use Nuwave\Lighthouse\Schema\DirectiveLocator as LighthouseDirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\EqDirective as LighthouseEqDirective;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;
use Nuwave\Lighthouse\Validation\ValidatorDirective as LighthouseValidatorDirective;

class Provider extends ServiceServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        CacheExpiredListener::class,
    ];

    public function register(): void {
        parent::register();

        $this->registerGraphQL();
        $this->registerGraphQLListeners();
        $this->registerPlayground();
        $this->registerIntrospection();
    }

    protected function registerGraphQL(): void {
        $this->app->singleton(LighthouseDirectiveLocator::class, DirectiveLocator::class);
        $this->app->bind(LighthouseValidatorDirective::class, ValidatorDirective::class);
        $this->app->bind(LighthouseEqDirective::class, EqDirective::class);

        $this->app->bind(SearchByDirective::class, SearchBy::class);
        $this->app->bind(SearchByTypeCondition::class, ConditionType::class);
        $this->app->bind(SearchByOperatorContainsDirective::class, ContainsOperator::class);
        $this->app->bind(SearchByOperatorEndsWithDirective::class, EndsWithOperator::class);
        $this->app->bind(SearchByOperatorRelationDirective::class, RelationOperator::class);

        $this->app->bind(SortByDirective::class, SortBy::class);
        $this->app->bind(SortByClause::class, ClauseType::class);
    }

    protected function registerGraphQLListeners(): void {
        $this->booting(static function (
            Dispatcher $dispatcher,
            LighthouseDirectiveLocator $locator,
            ErrorFormatter $formatter,
        ): void {
            $dispatcher->subscribe($locator);
            $dispatcher->listen(
                ManipulateResult::class,
                static function (ManipulateResult $event) use ($formatter): void {
                    $event->result->setErrorFormatter($formatter);
                },
            );
        });
    }

    protected function registerPlayground(): void {
        $this->app->afterResolving(
            Gate::class,
            static function (Gate $gate, Container $container): void {
                $gate->define('graphql-playground', static::getGateCallback($container->make(Repository::class)));
            },
        );
    }

    protected function registerIntrospection(): void {
        $this->app->bind(ProvidesValidationRules::class, ValidationRulesProvider::class);
        $this->app->afterResolving(
            Gate::class,
            static function (Gate $gate, Container $container): void {
                $gate->define('graphql-introspection', static::getGateCallback($container->make(Repository::class)));
            },
        );
    }

    /**
     * @return Closure(?Authenticatable): bool
     */
    protected static function getGateCallback(Repository $config): Closure {
        return static function (?Authenticatable $user) use ($config): bool {
            return (bool) $config->get('app.debug');
        };
    }
}
