<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;

use function array_keys;
use function count;
use function reset;

/**
 * Groups sequence of insert requests into one. Although all model events will
 * be dispatched the real save may happen a bit late. This is an experimental
 * feature and it should be enabled explicitly.
 *
 * @see \App\Utils\Eloquent\SmartSave\BatchInsert::enable()
 */
class BatchInsert {
    protected const LIMIT = 25;

    /**
     * @internal
     */
    public static ?BatchInsert $instance = null;
    /**
     * @internal
     */
    protected static bool $enabled = false;

    protected ?Model $model = null;

    /**
     * @var array<array<string,mixed>>
     */
    protected array $inserts = [];

    public function __construct() {
        // empty
    }

    public static function isEnabled(): bool {
        return static::$enabled;
    }

    public static function enable(Closure $closure): mixed {
        $previous        = static::$enabled;
        static::$enabled = true;

        try {
            return $closure();
        } finally {
            static::$enabled = $previous;
        }
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function __invoke(EloquentBuilder $builder, array $attributes): void {
        if (!$this->isSame($builder) || count($this->inserts) >= static::LIMIT) {
            $this->save();
        }

        $this->model     = $builder->getModel();
        $this->inserts[] = $attributes;
    }

    protected function isSame(EloquentBuilder $builder): bool {
        return $this->model
            && $this->model->getConnection() === $builder->getConnection()
            && $this->model->getTable() === $builder->getModel()->getTable();
    }

    public function save(): void {
        // Possible?
        if (!$this->model || !$this->inserts) {
            $this->reset();

            return;
        }

        // Save (single query will be the same as without batch)
        if ($this->model instanceof Upsertable) {
            $this->upsert();
        } else {
            $this->insert();
        }

        // Reset
        $this->reset();
    }

    protected function upsert(): void {
        // Possible?
        if (!($this->model instanceof Upsertable)) {
            $this->insert();

            return;
        }

        // Upsert
        $primary = $this->model->getKeyName();
        $unique  = $this->model::getUniqueKey();
        $update  = Arr::except(reset($this->inserts), [$primary, ...$unique]);
        $update  = array_keys($update);

        $this->query()->upsert($this->inserts, $unique, $update);
    }

    protected function insert(): void {
        if (count($this->inserts) === 1) {
            $this->query()->insert(reset($this->inserts));
        } else {
            $this->query()->insert($this->inserts);
        }
    }

    protected function query(): QueryBuilder {
        return $this->model->query()->toBase();
    }

    protected function reset(): void {
        $this->model   = null;
        $this->inserts = [];
    }
}
