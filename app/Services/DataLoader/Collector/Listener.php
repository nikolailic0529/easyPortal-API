<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Events\Subscriber;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

use function reset;

class Listener implements Subscriber {
    /**
     * @var WeakMap<Collector, Collector>
     */
    private WeakMap $collectors;

    public function __construct() {
        $this->collectors = new WeakMap();
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            'eloquent.saved: *',
            function (string $name, array $args): void {
                $model = reset($args);

                if ($model instanceof Model) {
                    $this->notify($model);
                }
            },
        );
        $dispatcher->listen(
            'eloquent.deleted: *',
            function (string $name, array $args): void {
                $model = reset($args);

                if ($model instanceof Model) {
                    $this->notify($model);
                }
            },
        );
    }

    protected function notify(Model $model): void {
        foreach ($this->collectors as $collector) {
            $collector->modelChanged($model);
        }
    }

    public function onModelChange(Collector $collector): void {
        $this->collectors[$collector] = $collector;
    }
}
