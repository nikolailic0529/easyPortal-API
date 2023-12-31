<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;

use function array_merge;
use function array_pop;
use function config;

class DataLoaderListener extends Listener {
    /**
     * @var array<DataLoaderRequest>
     */
    protected array $stack = [];

    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            RequestStarted::class,
            RequestSuccessful::class,
            RequestFailed::class,
        ];
    }

    public function __invoke(object $event): void {
        $this->call(function () use ($event): void {
            if ($event instanceof RequestStarted) {
                $this->requestStarted($event);
            } elseif ($event instanceof RequestSuccessful) {
                $this->requestSuccess($event);
            } elseif ($event instanceof RequestFailed) {
                $this->requestFailed($event);
            } else {
                // empty
            }
        });
    }

    protected function requestStarted(RequestStarted $event): void {
        $object    = new DataLoaderRequestObject($event);
        $context   = $event->getVariables();
        $enabled   = config('ep.logger.data_loader.queries');
        $countable = [
            "{$this->getCategory()}.total.requests.requests"                => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.requests" => 1,
        ];

        if ($object->isMutation()) {
            $enabled   = config('ep.logger.data_loader.mutations');
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
                $this->getRequestAction($object),
                $object,
                $context,
            ));
        } else {
            $this->stack[] = new DataLoaderRequest($object);
        }
    }

    protected function requestSuccess(RequestSuccessful $event): void {
        if (!$this->stack) {
            return;
        }

        $object      = new DataLoaderRequestObject($event);
        $request     = array_pop($this->stack);
        $duration    = $request->getDuration();
        $context     = [
            'objects' => $object->getCount(),
        ];
        $countable   = [
            "{$this->getCategory()}.total.requests.success"                 => 1,
            "{$this->getCategory()}.total.requests.duration"                => $duration,
            "{$this->getCategory()}.requests.{$object->getType()}.success"  => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.objects"  => $object->getCount(),
            "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
        ];
        $transaction = $request->getTransaction();

        if ($transaction) {
            $this->logger->success($transaction, $context, $countable);
        } else {
            $this->logger->count($countable);
        }
    }

    protected function requestFailed(RequestFailed $event): void {
        if (!$this->stack) {
            return;
        }

        $object      = new DataLoaderRequestObject($event);
        $request     = array_pop($this->stack);
        $duration    = $request->getDuration();
        $context     = [
            'params'    => $event->getVariables(),
            'response'  => $event->getResponse(),
            'exception' => $event->getException(),
        ];
        $countable   = [
            "{$this->getCategory()}.total.requests.failed"                  => 1,
            "{$this->getCategory()}.total.requests.duration"                => $duration,
            "{$this->getCategory()}.requests.{$object->getType()}.failed"   => 1,
            "{$this->getCategory()}.requests.{$object->getType()}.objects"  => $object->getCount(),
            "{$this->getCategory()}.requests.{$object->getType()}.duration" => $duration,
        ];
        $transaction = $request->getTransaction();

        if ($transaction) {
            $this->logger->fail($transaction, $context, $countable);
        } else {
            $this->logger->event(
                $this->getCategory(),
                $this->getRequestAction($object),
                Status::failed(),
                $object,
                $context,
                $countable,
            );
        }
    }

    protected function getRequestAction(DataLoaderRequestObject $object): string {
        return $object->isMutation() ? 'graphql.mutation' : 'graphql.query';
    }

    protected function getCategory(): Category {
        return Category::dataLoader();
    }
}
