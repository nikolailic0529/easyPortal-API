<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Enums\Level;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use App\Services\Logger\Models\Log;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LogicException;

use function array_column;
use function array_merge;
use function array_shift;
use function array_unshift;
use function microtime;
use function round;

class Logger {
    protected ?Log  $log   = null;
    protected float $start = 0;
    protected int   $index = 0;

    /**
     * @var array<array{log:\App\Services\Logger\Models\Log,start:float,index:int}>
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
    public function start(Type $type, Level $level, string $action, array $context = null): string {
        // Stack
        $index  = null;
        $parent = null;

        if ($this->log) {
            $index  = $this->index++;
            $parent = $this->log;

            array_unshift($this->stack, [
                'log'   => $this->log,
                'start' => $this->start,
                'index' => $this->index,
            ]);
        }

        // Create
        $this->start        = microtime(true);
        $this->index        = 0;
        $this->log          = new Log();
        $this->log->level   = $level;
        $this->log->type    = $type;
        $this->log->action  = $action;
        $this->log->index   = $index;
        $this->log->parent  = $parent;
        $this->log->status  = Status::active();
        $this->log->context = $this->mergeContext($context);

        $this->log->save();

        // Return
        return $this->log->getKey();
    }

    /**
     * @param array<mixed>|null $context
     */
    public function success(string $transaction, array $context = null): void {
        $this->end($transaction, Status::success(), $context);
    }

    /**
     * @param array<mixed>|null $context
     */
    public function fail(string $transaction, array $context = null): void {
        $this->end($transaction, Status::failed(), $context);
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    public function event(
        Level $level,
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
        $entry          = new Log();
        $entry->type    = Type::event();
        $entry->action  = $action;
        $entry->index   = $this->index++;
        $entry->parent  = $this->log;
        $entry->status  = null;
        $entry->context = $context;

        if ($object) {
            $entry->object_type = $object->getMorphClass();
            $entry->object_id   = $object->getKey();
        }

        $entry->save();

        // Update countable
        $this->count(array_merge($countable, [
            "levels_{$level}" => 1,
        ]));
    }

    /**
     * @param array<string,int> $countable
     */
    public function count(array $countable = []): void {
        // Recording?
        if (!$this->isRecording()) {
            return;
        }

        // Update
        $logs = [$this->log, array_column($this->stack, 'log')];

        foreach ($logs as $log) {
            foreach ($countable as $property => $value) {
                $log->statistic->{$property} += $value;
            }
        }
    }

    /**
     * @param array<mixed> $context
     */
    protected function end(string $transaction, Status $status, array $context = []): void {
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
        $this->index = $parent['index'] ?? 0;
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function mergeContext(array|null $context): ?array {
        $current   = $this->log->context ?: [];
        $current[] = [
            'status'  => $this->log->status,
            'context' => $context,
        ];
        $current   = $this->prepareContext($current);

        return $current;
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function prepareContext(array|null $context): ?array {
        // TODO [Logger] Serialize exceptions

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
