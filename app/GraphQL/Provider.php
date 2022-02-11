<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\GraphQL\ErrorFormatter;
use App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Complex\Relation as RelationOperator;
use App\GraphQL\Extensions\Lighthouse\DirectiveLocator;
use App\GraphQL\Extensions\Lighthouse\Directives\EqDirective;
use App\GraphQL\Extensions\Lighthouse\Directives\ValidatorDirective;
use App\GraphQL\Listeners\CacheExpiredListener;
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
use Nuwave\Lighthouse\Schema\DirectiveLocator as LighthouseDirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\EqDirective as LighthouseEqDirective;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;
use Nuwave\Lighthouse\Validation\ValidatorDirective as LighthouseValidatorDirective;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerGraphQL();
        $this->registerListeners();
        $this->registerPlayground();
        $this->registerIntrospection();
    }

    protected function registerGraphQL(): void {
        $this->app->singleton(LighthouseDirectiveLocator::class, DirectiveLocator::class);
        $this->app->bind(LighthouseValidatorDirective::class, ValidatorDirective::class);
        $this->app->bind(LighthouseEqDirective::class, EqDirective::class);
        $this->app->bind(SearchByRelationOperator::class, RelationOperator::class);
    }

    protected function registerListeners(): void {
        $this->booting(static function (
            Dispatcher $dispatcher,
            LighthouseDirectiveLocator $locator,
            ErrorFormatter $formatter,
        ): void {
            $dispatcher->subscribe(CacheExpiredListener::class);
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

    protected static function getGateCallback(Repository $config): Closure {
        return static function (?Authenticatable $user) use ($config): bool {
            return (bool) $config->get('app.debug');
        };
    }
}
