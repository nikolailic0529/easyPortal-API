<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;

use function array_pop;
use function last;

class QueueListener extends Listener {
    /**
     * @var array<array<string,string>>
     */
    protected array $stack = [];

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(
            JobProcessing::class,
            $this->getSafeListener(function (JobProcessing $event): void {
                $this->started($event);
            }),
        );

        $dispatcher->listen(
            JobProcessed::class,
            $this->getSafeListener(function (JobProcessed $event): void {
                $this->success($event);
            }),
        );

        $dispatcher->listen(
            JobFailed::class,
            $this->getSafeListener(function (JobFailed $event): void {
                $this->failed($event);
            }),
        );

        $dispatcher->listen(
            JobExceptionOccurred::class,
            $this->getSafeListener(function (JobExceptionOccurred $event): void {
                $this->failed($event);
            }),
        );

        Queue::createPayloadUsing(function (string $connection, string $queue, array $payload): array {
            $this->getSafeListener(function () use ($connection, $queue, $payload): void {
                $this->dispatched(new QueueJob($connection, $queue, $payload));
            })();

            return [];
        });
    }

    protected function dispatched(JobContract $job): void {
        $object = new QueueObject($job);

        $this->logger->event(
            $this->getCategory(),
            'job.dispatched',
            Status::success(),
            $object,
            $this->getContext($job),
            [
                "{$this->getCategory()}.total.dispatched"                => 1,
                "{$this->getCategory()}.dispatched.{$object->getType()}" => 1,
            ],
        );
    }

    protected function started(JobProcessing $event): void {
        $this->stack[] = [
            $event->job->uuid(),
            $this->logger->start(
                $this->getCategory(),
                'job.run',
                new QueueObject($event->job),
                $this->getContext($event->job),
            ),
        ];
    }

    protected function success(JobProcessed $event): void {
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            $this->logger->success($transaction);
        }

        $this->killZombies($event->job);
    }

    protected function failed(JobExceptionOccurred|JobFailed $event): void {
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            $this->logger->fail($transaction, [
                'exception' => $event->exception,
            ]);
        }

        $this->killZombies($event->job);
    }

    /**
     * @return array<string,mixed>
     */
    protected function getContext(JobContract $job): array {
        return [
            'id'         => $job->uuid(),
            'connection' => $job->getConnectionName(),
            'queue'      => $job->getQueue(),
            'payload'    => $job->payload(),
        ];
    }

    protected function getCategory(): Category {
        return Category::queue();
    }

    protected function killZombies(JobContract $job): void {
        // If a job was killed (eg `kill -9`) database may contain records with
        // status equal to "active", these records will have this status forever.
        // To avoid this we should reset the status for existing "active" jobs.
        $object  = new QueueObject($job);
        $zombies = Log::query()
            ->where('category', '=', $this->getCategory())
            ->where('action', '=', 'job.run')
            ->where('object_type', '=', $object->getType())
            ->where('object_id', '=', $object->getId())
            ->where('status', '=', Status::active())
            ->get();

        foreach ($zombies as $zombie) {
            $zombie->status = Status::killed();
            $zombie->save();
        }
    }
}
