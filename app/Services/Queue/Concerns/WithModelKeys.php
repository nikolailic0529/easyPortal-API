<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Job;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @mixin Job
 */
trait WithModelKeys {
    /**
     * @use WithModel<TModel>
     */
    use WithModel;

    /**
     * @private should be protected for serialization
     *
     * @var array<string|int>
     */
    protected array $keys;

    /**
     * @return array<string|int>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param array<string|int> $keys
     */
    protected function setKeys(array $keys): static {
        $this->keys = $keys;

        return $this;
    }

    /**
     * @param class-string<TModel> $model
     * @param array<string|int>    $keys
     *
     * @return $this
     */
    public function init(string $model, array $keys): static {
        $this->setModel($model);
        $this->setKeys($keys);
        $this->initialized();

        return $this;
    }
}
