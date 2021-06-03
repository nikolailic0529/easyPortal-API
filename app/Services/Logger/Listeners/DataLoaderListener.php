<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestStarted;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

use function array_pop;
use function mb_strtolower;
use function str_starts_with;
use function trim;

class DataLoaderListener implements Subscriber {
    /**
     * @var array<string>
     */
    protected array $stack = [];

    public function __construct(
        protected Logger $logger,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(RequestStarted::class, function (RequestStarted $event): void {
            $this->started($event);
        });

        $dispatcher->listen(RequestSuccessful::class, function (RequestSuccessful $event): void {
            $this->success($event);
        });

        $dispatcher->listen(RequestFailed::class, function (RequestFailed $event): void {
            $this->failed($event);
        });
    }

    protected function started(RequestStarted $event): void {
        $action  = 'graphql.query';
        $context = null;

        if ($this->isMutation($event->getRequest())) {
            $action  = 'graphql.mutation';
            $context = $event->getRequest();
        }

        $this->stack[] = $this->logger->start(
            Category::dataLoader(),
            $action,
            $context,
        );
    }

    protected function success(RequestSuccessful $event): void {
        $this->logger->success(array_pop($this->stack), [], [
            'data-loader.requests.success' => 1,
        ]);
    }

    protected function failed(RequestFailed $event): void {
        $this->logger->fail(
            array_pop($this->stack),
            [
                'request'   => $event->getRequest(),
                'response'  => $event->getResponse(),
                'exception' => $event->getException(),
            ],
            [
                'data-loader.requests.failed' => 1,
            ],
        );
    }

    /**
     * @param array<mixed> $data
     */
    protected function isMutation(array $data): bool {
        return str_starts_with(mb_strtolower(trim((string) $this->getQuery($data))), 'mutation ');
    }

    /**
     * @param array<mixed> $data
     */
    protected function getQuery(array $data): ?string {
        // Multipart?
        if (!isset($data['query'])) {
            $data = Arr::first($data, static function (mixed $value): bool {
                return isset($value['name']) && $value['name'] === 'operations';
            });
        }

        // Get query
        $query = $data['query'] ?? null;

        // Return
        return $query;
    }
}
