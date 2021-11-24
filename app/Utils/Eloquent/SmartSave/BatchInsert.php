<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function __destruct() {
        $this->save();
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
    public function __invoke(Builder $builder, array $attributes): void {
        if (!$this->isSame($builder) || count($this->inserts) >= static::LIMIT) {
            $this->save();
        }

        $this->model     = $builder->getModel();
        $this->inserts[] = $attributes;
    }

    protected function isSame(Builder $builder): bool {
        return $this->model
            && $this->model->getConnection() === $builder->getConnection()
            && $this->model->getTable() === $builder->getModel()->getTable();
    }

    protected function save(): void {
        // Possible?
        if (!$this->model || !$this->inserts) {
            return;
        }

        // Save (single query will be the same as without batch)
        if (count($this->inserts) === 1) {
            $this->model->query()->toBase()->insert(reset($this->inserts));
        } else {
            $this->model->query()->toBase()->insert($this->inserts);
        }

        // Reset
        $this->model   = null;
        $this->inserts = [];
    }
}
