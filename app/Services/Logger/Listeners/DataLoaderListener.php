<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use Illuminate\Contracts\Events\Dispatcher;

use function array_merge;
use function array_pop;

class DataLoaderListener extends Listener {
    /**
     * @var array<\App\Services\Logger\Listeners\DataLoaderRequest>
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
        $object    = new DataLoaderObject($event);
        $context   = $event->getParams();
        $enabled   = $this->config->get('ep.logger.data_loader.queries');
        $countable = [
            "{$this->getCategory()}.total.requests.requests"                => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.requests" => 1,
        ];

        if ($object->isMutation()) {
            $enabled   = $this->config->get('ep.logger.data_loader.mutations');
            $countable = array_merge($countable, [
                "{$this->getCategory()}.total.requests.mutations" => 1,
            ]);
        } else {
            $countable = array_merge($countable, [
                "{$this->getCategory()}.total.requests.queries" => 1,
            ]);
        }

        $this->logger->count($countable);

        if ($enabled) {
            $this->stack[] = new DataLoaderRequest($object, $this->logger->start(
                $this->getCategory(),
                $this->getAction($object),
                $object,
                $context,
            ));
        } else {
            $this->stack[] = new DataLoaderRequest($object);
        }
    }

    protected function success(RequestSuccessful $event): void {
        $object    = new DataLoaderObject($event);
        $request   = array_pop($this->stack);
        $duration  = $request->getDuration();
        $countable = [
            "{$this->getCategory()}.total.requests.success"                 => 1,
            "{$this->getCategory()}.total.requests.duration"                => $duration,
            "{$this->getCategory()}.requests.{$object->getType()}.success"  => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.objects"  => $object->getCount(),
            "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
        ];

        if ($request->getTransaction()) {
            $this->logger->success($request->getTransaction(), [], $countable);
        } else {
            $this->logger->count($countable);
        }
    }

    protected function failed(RequestFailed $event): void {
        $object    = new DataLoaderObject($event);
        $request   = array_pop($this->stack);
        $duration  = $request->getDuration();
        $context   = [
            'params'    => $event->getParams(),
            'response'  => $event->getResponse(),
            'exception' => $event->getException()?->getMessage(),
        ];
        $countable = [
            "{$this->getCategory()}.total.requests.failed"                  => 1,
            "{$this->getCategory()}.total.requests.duration"                => $duration,
            "{$this->getCategory()}.requests.{$object->getType()}.failed"   => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.objects"  => $object->getCount(),
            "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
        ];

        if ($request->getTransaction()) {
            $this->logger->fail(
                $request->getTransaction(),
                $context,
                $countable,
            );
        } else {
            $this->logger->event(
                $this->getCategory(),
                $this->getAction($object),
                Status::failed(),
                $object,
                $context,
                $countable,
            );
        }
    }

    protected function getAction(DataLoaderObject $object): string {
        return $object->isMutation() ? 'graphql.mutation' : 'graphql.query';
    }

    protected function getCategory(): Category {
        return Category::dataLoader();
    }
}
