<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\GraphQL\ErrorFormatter;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex\Relation as RelationOperator;
use App\GraphQL\Extensions\Lighthouse\Directives\EqDirective;
use App\GraphQL\Providers\ValidationRulesProvider;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex\Relation as SearchByRelationOperator;
use Nuwave\Lighthouse\Events\ManipulateResult;
use Nuwave\Lighthouse\Schema\Directives\EqDirective as LighthouseEqDirective;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;

class Provider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $dispatcher): void {
        $this->bootGraphQL($dispatcher);
        $this->bootPlayground();
        $this->bootIntrospection();
    }

    protected function bootGraphQL(Dispatcher $dispatcher): void {
        $this->app->bind(LighthouseEqDirective::class, EqDirective::class);
        $this->app->bind(SearchByRelationOperator::class, RelationOperator::class);

        $dispatcher->listen(
            ManipulateResult::class,
            function (ManipulateResult $event): void {
                $event->result->setErrorFormatter($this->app->make(ErrorFormatter::class));
            },
        );
    }

    protected function bootPlayground(): void {
        $this->app->afterResolving(
            Gate::class,
            static function (Gate $gate, Container $container): void {
                $gate->define('graphql-playground', static::getGateCallback($container->make(Repository::class)));
            },
        );
    }

    protected function bootIntrospection(): void {
        $this->app->bind(ProvidesValidationRules::class, ValidationRulesProvider::class);
        $this->app->afterResolving(
            Gate::class,
            static function (Gate $gate, Container $container): void {
                $gate->define('graphql-introspection', static::getGateCallback($container->make(Repository::class)));
            },
        );
    }

    protected static function getGateCallback(Repository $config): Closure {
        return static function (?Authenticatable $user) use ($config): bool {
            return (bool) $config->get('app.debug');
        };
    }
}
