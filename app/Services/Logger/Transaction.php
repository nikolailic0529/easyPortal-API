<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Enums\Level;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Enums\Type;
use App\Services\Logger\Models\Log;
use App\Services\Logger\Models\LogEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

use function microtime;
use function round;

class Transaction {
    protected float  $start;
    protected string $id;
    protected Log    $log;
    protected bool   $active     = true;
    protected int    $logIndex   = 0;
    protected int    $entryIndex = 0;

    /**
     * @param array<mixed>|null $context
     */
    public function __construct(
        Type $type,
        string $action,
        array $context = null,
        Transaction $parent = null,
        string $id = null,
    ) {
        $this->id           = $id ?: Str::uuid();
        $this->start        = microtime(true);
        $this->log          = new Log();
        $this->log->id      = $id;
        $this->log->type    = $type;
        $this->log->action  = $action;
        $this->log->index   = 0;
        $this->log->status  = Status::active();
        $this->log->parent  = null;
        $this->log->context = $context;

        if ($parent) {
            $this->log->index  = $parent->logIndex++;
            $this->log->parent = $parent->getLog();
        }

        $this->log->save();
    }

    public function getId(): string {
        return $this->id;
    }

    protected function getLog(): Log {
        return $this->log;
    }

    protected function isActive(): bool {
        return $this->active;
    }

    public function success(): void {
        $this->end(Status::success());
    }

    public function fail(): void {
        $this->end(Status::failed());
    }

    /**
     * @param array<mixed>|null $context
     */
    public function log(Level $level, string $event, Model $object = null, array $context = null): void {
        // Active?
        if (!$this->isActive()) {
            return;
        }

        // Add entry
        $entry          = new LogEntry();
        $entry->log     = $this->log;
        $entry->index   = $this->entryIndex++;
        $entry->level   = $level;
        $entry->event   = $event;
        $entry->context = $context;

        if ($object) {
            $entry->object_type = $object->getMorphClass();
            $entry->object_id   = $object->getKey();
        }

        $entry->save();

        // Update countable
        $log       = $this->log;
        $countable = $this->getCountable($entry);

        do {
            foreach ($countable as $property) {
                $log->{$property}++;
            }

            $log = $log->parent;
        } while ($log);
    }

    protected function end(Status $status): void {
        $this->active           = false;
        $this->log->status      = $status;
        $this->log->duration    = round((microtime(true) - $this->start) * 1000);
        $this->log->finished_at = Date::now();
        $this->log->save();
    }

    /**
     * @return array<string>
     */
    protected function getCountable(LogEntry $entry): array {
        $countable = [
            "entries_{$entry->level}",
        ];

        if ($entry->event === 'eloquent.created') {
            $countable[] = 'models_created';
        } elseif ($entry->event === 'eloquent.updated') {
            $countable[] = 'models_updated';
        } elseif ($entry->event === 'eloquent.restored') {
            $countable[] = 'models_restored';
        } elseif ($entry->event === 'eloquent.deleted') {
            $countable[] = 'models_deleted';
        } elseif ($entry->event === 'eloquent.forceDeleted') {
            $countable[] = 'models_force_deleted';
        } else {
            // empty
        }

        return $countable;
    }
}
