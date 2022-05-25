<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Services\DataLoader\Container\Singleton;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

class Collector implements Singleton {
    /**
     * @var WeakMap<Data,Data>
     */
    private WeakMap $subscribers;

    public function __construct(Listener $listener) {
        $this->subscribers = new WeakMap();

        $listener->onModelChange($this);
    }

    public function collect(mixed $object): void {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->collect($object);
        }
    }

    public function modelChanged(Model $model): void {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->collectObjectChange($model);
        }
    }

    public function subscribe(Data $subscriber): void {
        $this->subscribers[$subscriber] = $subscriber;
    }
}
