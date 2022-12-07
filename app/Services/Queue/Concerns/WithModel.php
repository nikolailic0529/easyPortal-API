<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Job;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @mixin Job
 */
trait WithModel {
    /**
     * @private should be protected for serialization
     *
     * @var class-string<TModel>
     */
    protected string $model;

    /**
     * @return class-string<TModel>
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * @param class-string<TModel> $model
     */
    protected function setModel(string $model): static {
        $this->model = $model;

        return $this;
    }

    /**
     * @param class-string<TModel> $model
     *
     * @return $this
     */
    public function init(string $model): static {
        $this->setModel($model);
        $this->initialized();

        return $this;
    }
}
