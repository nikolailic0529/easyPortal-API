<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Services\Auth\Listeners\SignIn;
use App\Utils\Providers\EventServiceProvider;
use App\Utils\Providers\EventsProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;

class Provider extends EventServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        SignIn::class,
    ];

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
