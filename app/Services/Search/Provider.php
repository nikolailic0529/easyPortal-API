<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builder as SearchBuilder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;

use function is_a;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerScopes();
    }

    protected function registerScopes(): void {
        $this->app->afterResolving(Builder::class, static function (Builder $builder, Container $container): void {
            $model  = $builder->model;
            $scopes = $model->getGlobalScopes();

            foreach ($scopes as $scope) {
                if (is_a($scope, Scope::class, true)) {
                    $container->make($scope)->applyForSearch($builder, $model);
                }
            }
        });
    }

    protected function registerBuilder(): void {
        $this->app->bind(Builder::class, SearchBuilder::class);
    }
}
