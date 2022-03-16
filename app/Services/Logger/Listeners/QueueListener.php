<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Action;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use App\Services\Queue\Events\JobStopped;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Laravel\Horizon\Events\JobDeleted;
use WeakMap;

use function array_pop;
use function last;

class QueueListener extends Listener {
    /**
     * @var array<array<string,string>>
     */
    protected array $stack = [];

    /**
     * @var WeakMap<JobContract>
     */
    protected WeakMap $stopped;

    public function __construct(Logger $logger, Repository $config, ExceptionHandler $exceptionHandler) {
        parent::__construct($logger, $config, $exceptionHandler);

        $this->stopped = new WeakMap();
    }

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

        $dispatcher->listen(
            JobStopped::class,
            $this->getSafeListener(function (JobStopped $event): void {
                $this->stopped($event);
            }),
        );

        $dispatcher->listen(
            JobDeleted::class,
            $this->getSafeListener(function (JobDeleted $event): void {
                $this->deleted($event);
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
        $object    = new QueueObject($job);
        $category  = $this->getCategory();
        $countable = [
            "{$category}.total.dispatched"                => 1,
            "{$category}.dispatched.{$object->getType()}" => 1,
        ];

        if ($object->isCronable()) {
            $this->logger->event(
                $category,
                (string) Action::queueJobDispatched(),
                Status::success(),
                $object,
                $this->getContext($job),
                $countable,
            );
        } else {
            $this->logger->count($countable);
        }
    }

    protected function started(JobProcessing $event): void {
        // Cronable?
        $object = new QueueObject($event->job);

        if (!$object->isCronable()) {
            return;
        }

        // There is no special event for timeouted jobs - they are just killed
        // and then will be restarted (if not failed).
        //
        // (Actual for Laravel v8.58.0)
        $this->killZombies($event->job);

        // Start
        $this->stack[] = [
            $event->job->uuid(),
            $this->logger->start(
                $this->getCategory(),
                (string) Action::queueJobRun(),
                $object,
                $this->getContext($event->job),
            ),
        ];
    }

    protected function success(JobProcessed $event): void {
        // Cronable?
        $object = new QueueObject($event->job);

        if (!$object->isCronable()) {
            return;
        }

        // Log
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            if (isset($this->stopped[$event->job])) {
                $this->logger->end($transaction, Status::stopped());
            } else {
                $this->logger->success($transaction);
            }
        }

        // Zombies
        $this->killZombies($event->job);
    }

    protected function failed(JobExceptionOccurred|JobFailed $event): void {
        // Cronable?
        $object = new QueueObject($event->job);

        if (!$object->isCronable()) {
            return;
        }

        // Log
        [$id, $transaction] = last($this->stack);

        if ($id === $event->job->uuid() && $transaction) {
            array_pop($this->stack);

            $this->logger->fail($transaction, [
                'exception' => $event->exception,
            ]);
        }

        // Zombies
        $this->killZombies($event->job);
    }

    protected function stopped(JobStopped $event): void {
        $this->stopped[$event->getJob()] = true;
    }

    protected function deleted(JobDeleted $event): void {
        // Job?
        if (!($event->job instanceof JobContract)) {
            return;
        }

        // Cronable?
        $object = new QueueObject($event->job);

        if (!$object->isCronable()) {
            return;
        }

        // Kill
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
            ->where('action', '=', Action::queueJobRun())
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
