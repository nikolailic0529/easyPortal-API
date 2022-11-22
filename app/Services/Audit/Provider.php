<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Services\Audit\Listeners\Audit;
use App\Services\Audit\Listeners\AuthListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->booting(static function (Dispatcher $dispatcher): void {
            $dispatcher->subscribe(Audit::class);
            $dispatcher->subscribe(AuthListener::class);
        });
    }
}
