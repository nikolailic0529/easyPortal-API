<?php declare(strict_types = 1);

namespace App\Services\Events\Eloquent;

use App\Events\Subscriber;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

use function reset;

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
