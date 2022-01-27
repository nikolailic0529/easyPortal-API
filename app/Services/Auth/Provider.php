<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->app->singleton(Permissions::class);
    }

    public function boot(): void {
        $this->app->afterResolving(
            GateContract::class,
            static function (GateContract $gate, Container $container): void {
                $auth = $container->make(Gate::class);

                $gate->before([$auth, 'before']);
                $gate->after([$auth, 'after']);
            },
        );
    }
}
