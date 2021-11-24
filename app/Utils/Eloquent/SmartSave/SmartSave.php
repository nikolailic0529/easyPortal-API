<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\SmartSave;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Models\Model
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
        $root = BatchInsert::$instance === null && BatchInsert::isEnabled();

        if ($root) {
            BatchInsert::$instance = new BatchInsert();
        }

        $result = parent::save($options);

        try {
            foreach ($this->onSaveCallbacks as $callback) {
                $callback();
            }

            $this->onSaveCallbacks = [];
        } finally {
            if ($root) {
                BatchInsert::$instance = null;
            }
        }

        return $result;
    }

    public function newModelQuery(): Builder {
        $query = parent::newModelQuery();

        if (BatchInsert::$instance) {
            $query->macro('insert', BatchInsert::$instance);
        }

        return $query;
    }
}
