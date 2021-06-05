<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;

use function array_pop;

class DataLoaderListener extends Listener {
    /**
     * @var array<string>
     */
    protected array $stack = [];

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(RequestStarted::class, $this->getSafeListener(function (RequestStarted $event): void {
            $this->started($event);
        }));

        $dispatcher->listen(RequestSuccessful::class, $this->getSafeListener(function (RequestSuccessful $event): void {
            $this->success($event);
        }));

        $dispatcher->listen(RequestFailed::class, $this->getSafeListener(function (RequestFailed $event): void {
            $this->failed($event);
        }));
    }

    protected function started(RequestStarted $event): void {
        $action  = 'graphql.query';
        $context = $event->getParams();
        $object  = new DataLoaderObject($event);

        if ($object->isMutation()) {
            $action = 'graphql.mutation';
        }

        $this->logger->count([
            "{$this->getCategory()}.total.requests.requests" => 1,
        ]);

        $this->stack[] = $this->logger->start(
            $this->getCategory(),
            $action,
            $object,
            $context,
        );
    }

    protected function success(RequestSuccessful $event): void {
        $object   = new DataLoaderObject($event);
        $duration = $this->logger->getDuration();

        $this->logger->success(array_pop($this->stack), [], [
            "{$this->getCategory()}.total.requests.success"                 => 1,
            "{$this->getCategory()}.total.requests.duration"                => $duration,
            "{$this->getCategory()}.requests.{$object->getType()}.success"  => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.results"  => $object->getCount(),
            "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
        ]);
    }

    protected function failed(RequestFailed $event): void {
        $object   = new DataLoaderObject($event);
        $duration = $this->logger->getDuration();

        $this->logger->fail(
            array_pop($this->stack),
            [
                'params'    => $event->getParams(),
                'response'  => $event->getResponse(),
                'exception' => $event->getException()?->getMessage(),
            ],
            [
                "{$this->getCategory()}.total.requests.failed"                  => 1,
                "{$this->getCategory()}.total.requests.duration"                => $duration,
                "{$this->getCategory()}.requests.{$object->getType()}.failed"   => 1,
                "{$this->getCategory()}.requests.{$object->getType()}.results"  => $object->getCount(),
                "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
            ],
        );
    }

    protected function getCategory(): Category {
        return Category::dataLoader();
    }
}
