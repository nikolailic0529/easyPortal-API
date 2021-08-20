<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use Closure;

/**
 * @mixin \App\Models\Model
 */
trait OnSave {
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
        $result = parent::save($options);

        foreach ($this->onSaveCallbacks as $callback) {
            $callback();
        }

        $this->onSaveCallbacks = [];

        return $result;
    }
}
