<?php declare(strict_types = 1);

namespace App\Services\Search\Processors\Concerns;

/**
 * @template TItem of \Illuminate\Database\Eloquent\Model
 */
trait WithModel {
    /**
     * @var class-string<TItem>
     */
    private string $model;

    /**
     * @return class-string<TItem>
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * @param class-string<TItem> $model
     */
    public function setModel(string $model): static {
        $this->model = $model;

        return $this;
    }
}
