<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;

use function array_pop;
use function mb_strtolower;
use function str_starts_with;
use function trim;

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

        if ($this->isMutation($event->getQuery())) {
            $action = 'graphql.mutation';
        }

        $this->logger->count([
            'data-loader.requests.total.requests' => 1,
        ]);

        $this->stack[] = $this->logger->start(
            Category::dataLoader(),
            $action,
            $object,
            $context,
        );
    }

    protected function success(RequestSuccessful $event): void {
        $object = new DataLoaderObject($event);

        $this->logger->success(array_pop($this->stack), [], [
            'data-loader.requests.total.duration'                        => $this->logger->getDuration(),
            'data-loader.requests.total.success'                         => 1,
            "data-loader.requests.requests.{$object->getType()}.success" => 1,
            "data-loader.requests.requests.{$object->getType()}.results" => $object->getCount(),
        ]);
    }

    protected function failed(RequestFailed $event): void {
        $object = new DataLoaderObject($event);

        $this->logger->fail(
            array_pop($this->stack),
            [
                'params'    => $event->getParams(),
                'response'  => $event->getResponse(),
                'exception' => $event->getException()?->getMessage(),
            ],
            [
                'data-loader.requests.total.duration'                        => $this->logger->getDuration(),
                'data-loader.requests.total.failed'                          => 1,
                "data-loader.requests.requests.{$object->getType()}.failed"  => 1,
                "data-loader.requests.requests.{$object->getType()}.results" => $object->getCount(),
            ],
        );
    }

    protected function isMutation(string $query): bool {
        return str_starts_with(mb_strtolower(trim($query)), 'mutation ');
    }
}
