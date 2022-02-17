<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Services\Recalculator\Listeners\DataImportedListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerListeners();
    }

    protected function registerListeners(): void {
        $this->booting(static function (Dispatcher $dispatcher): void {
            $dispatcher->subscribe(DataImportedListener::class);
        });
    }
}
