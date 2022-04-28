<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

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
 * @see \App\Utils\Eloquent\SmartSave\BatchSave::enable()
 */
class BatchInsert {
    protected const LIMIT = 25;

    protected ?Model $model = null;

    /**
     * @var array<int, array<string,mixed>>
     */
    protected array $inserts = [];

    public function __construct() {
        // empty
    }

    public function __destruct() {
        $this->save();
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function add(Model $model, array $attributes): bool {
        if (!$this->isSame($model) || count($this->inserts) >= static::LIMIT) {
            $this->save();
        }

        $this->model     = $model;
        $this->inserts[] = $attributes;

        return true;
    }

    protected function isSame(Model $model): bool {
        return $this->model
            && $this->model->getConnection() === $model->getConnection()
            && $this->model->getTable() === $model->getTable();
    }

    public function save(): void {
        // Possible?
        if (!$this->model || !$this->inserts) {
            $this->reset();

            return;
        }

        // Save (single query will be the same as without batch)
        try {
            if ($this->model instanceof Upsertable) {
                $this->upsert();
            } else {
                $this->insert();
            }
        } finally {
            $this->reset();
        }
    }

    protected function upsert(): void {
        // Possible?
        if (!($this->model instanceof Upsertable)) {
            $this->insert();

            return;
        }

        // Upsert
        $createdAt = $this->model->getCreatedAtColumn();
        $primary   = $this->model->getKeyName();
        $unique    = $this->model::getUniqueKey();
        $update    = Arr::except(reset($this->inserts), [$primary, $createdAt, ...$unique]);
        $update    = array_keys($update);

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
