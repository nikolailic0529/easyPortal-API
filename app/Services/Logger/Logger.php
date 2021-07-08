<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Casts\Statistics;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Date;
use LogicException;
use Throwable;

use function array_pop;
use function array_reverse;
use function array_slice;
use function count;
use function is_array;
use function sprintf;

class Logger {
    public const CONNECTION = 'logs';

    protected ?Action $action = null;
    protected int     $index  = 0;

    /**
     * @var array<\App\Services\Logger\Action>
     */
    protected array $stack = [];

    public function __construct(
        protected Factory $auth,
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @param array<mixed>|null $context
     */
    public function start(
        Category $category,
        string $action,
        LoggerObject $object = null,
        array $context = null,
    ): string {
        // Stack
        $parent = null;

        if ($this->action) {
            $parent        = $this->action->getLog();
            $this->stack[] = $this->action;
        }

        // Create
        $log           = new Log();
        $log->category = $category;
        $log->action   = $action;
        $log->index    = $this->index++;
        $log->parent   = $parent;
        $log->status   = Status::active();
        $log->context  = $this->mergeContext($log, $context);

        if ($object) {
            $log->object_type = $object->getType();
            $log->object_id   = $object->getId();
        }

        $log->save();

        // Add
        $this->action = new Action($log);

        // Return
        return $this->action->getKey();
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
        Status $status = null,
        LoggerObject $object = null,
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
        $entry->status   = $status;
        $entry->index    = $this->index++;
        $entry->parent   = $this->action->getLog();
        $entry->context  = $this->mergeContext($entry, $context);

        if ($object) {
            $entry->object_id   = $object->getId();
            $entry->object_type = $object->getType();
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
        /** @var array<\App\Services\Logger\Action> $actions */
        $actions = [...$this->stack, $this->action];
        $dump    = $this->config->get('ep.logger.dump');
        $dump    = $dump ? Date::now()->sub($dump) : null;

        foreach ($actions as $action) {
            $log        = $action->getLog();
            $statistics = $log->statistics ?? new Statistics();

            foreach ($countable as $property => $value) {
                $statistics->{$property} = $statistics->{$property} + $value;
            }

            $log->statistics = $statistics;
            $log->duration   = $action->getDuration();

            if ($dump && $log->updated_at <= $dump) {
                $log->save();
            }
        }
    }

    /**
     * @param array<mixed>|null $context
     * @param array<string,int> $countable
     */
    public function end(string $transaction, Status $status, array $context = null, array $countable = []): void {
        // Recording?
        if (!$this->isRecording()) {
            return;
        }

        // If some of the "end" call missed we should interrupt all children.
        $children = [];

        foreach ($this->stack as $item) {
            if ($children || $item->getKey() === $transaction) {
                $children[] = $item->getKey();
            }
        }

        $children[] = $this->action->getKey();

        if (count($children) > 1) {
            $children = array_slice($children, 1);
            $children = array_reverse($children);

            foreach ($children as $child) {
                $this->finish($child, Status::unknown());
            }
        } elseif (!$children) {
            // TODO [Logger] Should we log it?
            return;
        }

        // Finish
        $this->finish($transaction, $status, $context);

        // Count
        if ($status !== Status::active()) {
            $countable["{$this->getCategory()}.total.actions"]     = 1;
            $countable["{$this->getCategory()}.actions.{$status}"] = 1;
        }

        $this->count($countable);
    }

    /**
     * @param array<mixed>|null $context
     */
    private function finish(string $transaction, Status $status, array $context = null): void {
        // Valid?
        if ($this->action->getKey() !== $transaction) {
            throw new LogicException(sprintf(
                'Transaction id not match: `%s` !== `%s`',
                $this->action->getKey(),
                $transaction,
            ));
        }

        // Update
        $log              = $this->action->getLog();
        $log->status      = $status;
        $log->context     = $this->mergeContext($log, $context);
        $log->duration    = $this->getDuration();
        $log->finished_at = Date::now();

        $log->save();

        // Reset
        $this->action = array_pop($this->stack);

        if ($this->action === null) {
            $this->index = 0;
        }
    }

    /**
     * @param array<mixed>|null $context
     *
     * @return array<mixed>|null
     */
    protected function mergeContext(Log $log, array|null $context): ?array {
        $current = $log->context;

        if ($context) {
            $current   = $current ?: [];
            $current[] = [
                'status'  => $log->status,
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
        // TODO [Logger] We should use the same method as Laravel's Logger

        if (is_array($context)) {
            foreach ($context as $key => $value) {
                if ($value instanceof Throwable) {
                    $context[$key] = $value->getMessage();
                } elseif (is_array($value)) {
                    $context[$key] = $this->prepareContext($value);
                } else {
                    // no action
                }
            }
        }

        return $context ?: null;
    }

    protected function isRecording(): bool {
        return (bool) $this->action;
    }

    public function getDuration(): ?float {
        return $this->isRecording()
            ? $this->action->getDuration()
            : null;
    }

    protected function getCategory(): Category {
        return Category::logger();
    }
}
