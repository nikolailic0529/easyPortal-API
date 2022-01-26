<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\GraphQL\ErrorFormatter;
use App\GraphQL\Directives\Lighthouse\EqDirective;
use App\GraphQL\Directives\SearchBy\Operators\Complex\Relation as RelationOperator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex\Relation as SearchByRelationOperator;
use Nuwave\Lighthouse\Events\ManipulateResult;
use Nuwave\Lighthouse\Schema\Directives\EqDirective as LighthouseEqDirective;

class Provider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $dispatcher): void {
        $this->bootGraphQL($dispatcher);
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
}
