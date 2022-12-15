<?php declare(strict_types = 1);

namespace App\Utils\Providers;

use App\Services\Service;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ServiceServiceProvider extends ServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        // empty
    ];

    public function register(): void {
        parent::register();

        $this->registerService();
    }

    protected function registerService(): void {
        $service = Service::getService($this);

        if ($service) {
            $this->app->singleton($service);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool {
        return false;
    }

    public function listens(): mixed {
        $listens = parent::listens();

        foreach ($this->getListeners() as $listener) {
            $events = $listener::getEvents();

            foreach ($events as $event) {
                $listens[$event][] = $listener;
            }
        }

        return $listens;
    }

    /**
     * @return array<class-string<EventsProvider>>
     */
    protected function getListeners(): array {
        return $this->listeners;
    }
}
