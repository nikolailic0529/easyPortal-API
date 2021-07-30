<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builder as SearchBuilder;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerScopes();
        $this->registerBuilder();
    }

    protected function registerScopes(): void {
        $this->app->afterResolving(Builder::class, static function (SearchBuilder $builder): void {
            $model  = $builder->model;
            $scopes = $model->getGlobalScopes();

            foreach ($scopes as $scope) {
                if ($scope instanceof Scope) {
                    $scope->applyForSearch($builder, $model);
                }
            }
        });
    }

    protected function registerBuilder(): void {
        $this->app->bind(Builder::class, SearchBuilder::class);
    }
}
