<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Queue;

use function array_pop;
use function json_encode;

class QueueListener implements Subscriber {
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
        $dispatcher->listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->started($event);
        });

        $dispatcher->listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->success($event);
        });

        $dispatcher->listen(JobFailed::class, function (JobFailed $event): void {
            $this->failed($event);
        });

        Queue::createPayloadUsing(function (string $connection, string $queue, array $payload): array {
            $this->dispatched(new class($connection, $queue, $payload) extends Job implements JobContract {
                /**
                 * @param array<mixed> $payload
                 */
                public function __construct(
                    protected string $connectionName,
                    protected string $queue,
                    protected array $payload,
                ) {
                    // empty
                }

                /**
                 * @return array<mixed>
                 */
                public function payload(): array {
                    return $this->payload;
                }

                public function getJobId(): string|null {
                    return $this->payload()['id'] ?? null;
                }

                public function getRawBody(): string {
                    return json_encode($this->payload());
                }

                public function attempts(): int {
                    return ($this->payload()['attempts'] ?? 0) + 1;
                }
            });

            return [];
        });
    }

    protected function dispatched(JobContract $job): void {
        $this->logger->event(
            'job:dispatched',
            null,
            $this->getContext($job),
            [
                'jobs.dispatched' => 1,
            ],
        );
    }

    protected function started(JobProcessing $event): void {
        $this->stack[] = $this->logger->start(
            Category::jobs(),
            $event->job->getName(),
            $this->getContext($event->job),
        );
    }

    protected function success(JobProcessed $event): void {
        $this->logger->success(array_pop($this->stack));
    }

    protected function failed(JobFailed $event): void {
        $this->logger->fail(array_pop($this->stack), [
            'exception' => $event->exception,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function getContext(JobContract $job): array {
        return [
            'id'         => $job->uuid(),
            'name'       => $job->getName(),
            'connection' => $job->getConnectionName(),
            'queue'      => $job->getQueue(),
            'payload'    => $job->payload(),
        ];
    }
}
