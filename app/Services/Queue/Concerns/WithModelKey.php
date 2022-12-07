<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Job;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @mixin Job
 */
trait WithModelKey {
    /**
     * @use WithModel<TModel>
     */
    use WithModel;
    use WithKey;

    public function uniqueId(): string {
        return "{$this->getModel()}@{$this->getKey()}";
    }

    /**
     * @param class-string<TModel> $model
     *
     * @return $this
     */
    public function init(string $model, string|int $key): static {
        $this->setModel($model);
        $this->setKey($key);
        $this->initialized();

        return $this;
    }
}
