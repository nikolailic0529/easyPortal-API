<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Enums\Level;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use App\Services\Logger\Models\Log;
use App\Services\Logger\Models\LogEntry;
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
    protected ?Log  $log        = null;
    protected float $start      = 0;
    protected int   $logIndex   = 0;
    protected int   $entryIndex = 0;

    /**
     * @var array<array{log:\App\Services\Logger\Models\Log,start:float,logIndex:int,entryIndex:int}>
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
    public function start(Type $type, string $action, array $context = null): string {
        // Stack
        $index  = null;
        $parent = null;

        if ($this->log) {
            $index  = $this->entryIndex++;
            $parent = $this->log;

            array_unshift($this->stack, [
                'log'        => $this->log,
                'start'      => $this->start,
                'logIndex'   => $this->logIndex,
                'entryIndex' => $this->entryIndex,
            ]);
        }

        // Create
        $this->start        = microtime(true);
        $this->logIndex     = 0;
        $this->entryIndex   = 0;
        $this->log          = new Log();
        $this->log->type    = $type;
        $this->log->action  = $action;
        $this->log->index   = $index;
        $this->log->parent  = $parent;
        $this->log->status  = Status::active();
        $this->log->context = $this->mergeLogContext($this->log, $context);

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
     * @param array<string>     $countable
     */
    public function event(
        Level $level,
        string $event,
        Model $object = null,
        array $context = null,
        array $countable = [],
    ): void {
        // Recording?
        if (!$this->log) {
            return;
        }

        // Create entry
        $entry          = new LogEntry();
        $entry->log     = $this->log;
        $entry->index   = $this->entryIndex++;
        $entry->level   = $level;
        $entry->event   = $event;
        $entry->context = $this->prepareContext($context);

        if ($object) {
            $entry->object_type = $object->getMorphClass();
            $entry->object_id   = $object->getKey();
        }

        $entry->save();

        // Update countable
        $this->count($level, $countable);
    }

    /**
     * @param array<string> $countable
     */
    public function count(Level $level, array $countable = []): void {
        // Recording?
        if (!$this->log) {
            return;
        }

        // Update
        $logs      = [$this->log, array_column($this->stack, 'log')];
        $countable = array_merge($countable, [
            "entries_{$level}",
        ]);

        foreach ($logs as $log) {
            foreach ($countable as $property) {
                $log->{$property}++;
            }
        }
    }

    /**
     * @param array<mixed> $context
     */
    protected function end(string $transaction, Status $status, array $context = []): void {
        // Recording?
        if (!$this->log) {
            return;
        }

        // Valid?
        if ($this->log->getKey() !== $transaction) {
            throw new LogicException();
        }

        // Search required
        $this->log->status      = $status;
        $this->log->context     = $this->mergeLogContext($this->log, $context);
        $this->log->duration    = round((microtime(true) - $this->start) * 1000);
        $this->log->finished_at = Date::now();

        $this->log->save();

        // Reset
        $parent           = array_shift($this->stack);
        $this->log        = $parent['log'] ?? null;
        $this->start      = $parent['start'] ?? 0;
        $this->logIndex   = $parent['logIndex'] ?? 0;
        $this->entryIndex = $parent['entryIndex'] ?? 0;
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function mergeLogContext(Log $log, array|null $context): ?array {
        $current   = $log->context ?: [];
        $current[] = [
            'status'  => $log->status,
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
}
