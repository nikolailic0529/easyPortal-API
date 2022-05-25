<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Services\DataLoader\Container\Singleton;
use App\Utils\Eloquent\Events\OnModelDeleted;
use App\Utils\Eloquent\Events\OnModelSaved;
use App\Utils\Eloquent\Events\Subject;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

class Collector implements Singleton, OnModelSaved, OnModelDeleted {
    /**
     * @var WeakMap<Data,Data>
     */
    private WeakMap $subscribers;

    public function __construct(Subject $subject) {
        $this->subscribers = new WeakMap();

        $subject->onModelEvent($this);
    }

    public function collect(mixed $object): void {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->collect($object);
        }
    }

    protected function modelChanged(Model $model): void {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->collectObjectChange($model);
        }
    }

    public function subscribe(Data $subscriber): void {
        $this->subscribers[$subscriber] = $subscriber;
    }

    public function modelDeleted(Model $model): void {
        $this->modelChanged($model);
    }

    public function modelSaved(Model $model): void {
        $this->modelChanged($model);
    }
}
