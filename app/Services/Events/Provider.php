<?php declare(strict_types = 1);

namespace App\Services\Events;

use App\Events\Subscriber;
use App\Services\Events\Eloquent\Subject;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerSubscribers([
            Subject::class,
        ]);
    }

    /**
     * @param array<class-string<Subscriber>> $subscribers
     */
    protected function registerSubscribers(array $subscribers): void {
        foreach ($subscribers as $subscriber) {
            $this->app->singleton($subscriber);

            $this->booting(static function (Dispatcher $dispatcher) use ($subscriber): void {
                $dispatcher->subscribe($subscriber);
            });
        }
    }
}
