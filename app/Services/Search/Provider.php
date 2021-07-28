<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Scout\OwnedByOrganizationScope;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerScopes();
    }

    protected function registerScopes(): void {
        $this->app->afterResolving(Builder::class, static function (Builder $builder, Container $container): void {
            $container->make(OwnedByOrganizationScope::class)->apply($builder, $builder->model);
        });
    }
}
