<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Casts\Statistics;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LogicException;

use function array_column;
use function array_shift;
use function array_unshift;
use function microtime;
use function round;

class Logger {
    public const CONNECTION = 'logs';

    protected ?Log  $log   = null;
    protected float $start = 0;
    protected int   $index = 0;

    /**
     * @var array<array{log:\App\Services\Logger\Models\Log,start:float}>
     */
    protected array $stack = [];

    public function __construct(
        protected Factory $auth,
    ) {
        // empty
    }

    /**
     * @param array<mixed>|null $context
     */
    public function start(Category $category, string $action, array $context = null): string {
        // Stack
        $parent = null;

        if ($this->log) {
            $parent = $this->log;

            array_unshift($this->stack, [
                'log'   => $this->log,
                'start' => $this->start,
            ]);
        }

        // Create
        $this->start         = microtime(true);
        $this->log           = new Log();
        $this->log->category = $category;
        $this->log->action   = $action;
        $this->log->index    = $this->index++;
        $this->log->parent   = $parent;
        $this->log->status   = Status::active();
        $this->log->context  = $this->mergeContext($context);

        $this->log->save();

        // Return
        return $this->log->getKey();
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    public function success(string $transaction, array $context = null, array $countable = []): void {
        $this->end($transaction, Status::success(), $context, $countable);
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    public function fail(string $transaction, array $context = null, array $countable = []): void {
        $this->end($transaction, Status::failed(), $context, $countable);
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    public function event(
        Category $category,
        string $action,
        Model $object = null,
        array $context = null,
        array $countable = [],
    ): void {
        // Recording?
        if (!$this->isRecording()) {
            return;
        }

        // Create entry
        $entry           = new Log();
        $entry->category = $category;
        $entry->action   = $action;
        $entry->index    = $this->index++;
        $entry->parent   = $this->log;
        $entry->context  = $context ?: null;

        if ($object) {
            $entry->object_type = $object->getMorphClass();
            $entry->object_id   = $object->getKey();
        }

        $entry->save();

        // Update countable
        $this->count($countable);
    }

    /**
     * @param array<string,int> $countable
     */
    public function count(array $countable = []): void {
        // Recording or empty?
        if (!$countable || !$this->isRecording()) {
            return;
        }

        // Update
        $logs = [$this->log, ...array_column($this->stack, 'log')];

        foreach ($logs as $log) {
            $statistics = $log->statistics ?? new Statistics();

            foreach ($countable as $property => $value) {
                $statistics->{$property} = $statistics->{$property} + $value;
            }

            $log->statistics = $statistics;
        }
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    protected function end(string $transaction, Status $status, array $context = null, array $countable = []): void {
        // Recording?
        if (!$this->isRecording()) {
            return;
        }

        // Valid?
        if ($this->log->getKey() !== $transaction) {
            throw new LogicException();
        }

        // Search required
        $this->log->status      = $status;
        $this->log->context     = $this->mergeContext($context);
        $this->log->duration    = $this->getDuration();
        $this->log->finished_at = Date::now();

        $this->log->save();

        // Reset
        $parent      = array_shift($this->stack);
        $this->log   = $parent['log'] ?? null;
        $this->start = $parent['start'] ?? 0;

        if ($this->log === null) {
            $this->index = 0;
        }

        // Count
        if ($status === Status::failed()) {
            $countable['actions.failed'] = 1;
        }

        $this->count($countable);
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function mergeContext(array|null $context): ?array {
        $current = $this->log->context;

        if ($context) {
            $current   = $current ?: [];
            $current[] = [
                'status'  => $this->log->status,
                'context' => $context,
            ];
            $current   = $this->prepareContext($current);
        }

        return $current;
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function prepareContext(array|null $context): ?array {
        // TODO [Logger] Serialize exceptions?

        return $context ?: null;
    }

    protected function isRecording(): bool {
        return (bool) $this->log;
    }

    public function getDuration(): ?float {
        return $this->isRecording()
            ? round((microtime(true) - $this->start) * 1000)
            : null;
    }
}
