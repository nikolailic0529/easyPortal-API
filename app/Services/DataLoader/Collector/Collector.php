<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Services\DataLoader\Container\Singleton;
use WeakMap;

class Collector implements Singleton {
    /**
     * @var WeakMap<Data,Data>
     */
    private WeakMap $subscribers;

    public function __construct() {
        $this->subscribers = new WeakMap();
    }

    public function collect(mixed $object): void {
        foreach ($this->subscribers as $subscriber) {
            /** @var Data $subscriber */
            $subscriber->collect($object);
        }
    }

    public function subscribe(Data $subscriber): void {
        $this->subscribers[$subscriber] = $subscriber;
    }
}
