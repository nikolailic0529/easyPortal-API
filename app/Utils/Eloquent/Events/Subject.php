<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Events;

use App\Events\Subscriber;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

use function reset;

/**
 * Laravel doesn't provide any way to remove the concrete listener, but it is
 * required e.g. for Processors while process huge amount of objects when
 * listeners should be reset between chunks. This class designed specially
 * to solve this problem, so you can relax and don't worry about unsubscribing.
 */
class Subject implements Subscriber {
    /**
     * @var WeakMap<OnModelSaved, OnModelSaved>
     */
    private WeakMap $onSave;
    /**
     * @var WeakMap<OnModelDeleted, OnModelDeleted>
     */
    private WeakMap $onDelete;

    public function __construct() {
        $this->onSave   = new WeakMap();
        $this->onDelete = new WeakMap();
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            'eloquent.saved: *',
            function (string $name, array $args): void {
                $model = reset($args);

                if ($model instanceof Model) {
                    foreach ($this->onSave as $observer) {
                        $observer->modelSaved($model);
                    }
                }
            },
        );
        $dispatcher->listen(
            'eloquent.deleted: *',
            function (string $name, array $args): void {
                $model = reset($args);

                if ($model instanceof Model) {
                    foreach ($this->onDelete as $observer) {
                        $observer->modelDeleted($model);
                    }
                }
            },
        );
    }

    public function onModelEvent(object $observer): static {
        if ($observer instanceof OnModelSaved) {
            $this->onModelSaved($observer);
        }

        if ($observer instanceof OnModelDeleted) {
            $this->onModelDeleted($observer);
        }

        return $this;
    }

    public function onModelSaved(OnModelSaved $observer): static {
        $this->onSave[$observer] = $observer;

        return $this;
    }

    public function onModelDeleted(OnModelDeleted $observer): static {
        $this->onDelete[$observer] = $observer;

        return $this;
    }
}
