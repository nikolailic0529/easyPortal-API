<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait SmartSave {
    /**
     * @var array<\Closure>
     */
    private array $onSaveCallbacks = [];

    protected function onSave(Closure $callback): void {
        $this->onSaveCallbacks[] = $callback;
    }

    /**
     * @inheritdoc
     */
    public function save(array $options = []): bool {
        $root = BatchSave::$instance === null && BatchSave::isEnabled();

        if ($root) {
            BatchSave::$instance = new BatchInsert();
        }

        $result = parent::save($options);

        try {
            foreach ($this->onSaveCallbacks as $callback) {
                $callback();
            }

            $this->onSaveCallbacks = [];
        } finally {
            if ($root) {
                BatchSave::$instance?->save();
                BatchSave::$instance = null;
            }
        }

        return $result;
    }

    public function newModelQuery(): Builder {
        $query = parent::newModelQuery();

        if (BatchSave::$instance) {
            $query->macro('insert', BatchSave::$instance);
        }

        return $query;
    }
}
