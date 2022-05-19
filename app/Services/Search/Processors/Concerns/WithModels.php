<?php declare(strict_types = 1);

namespace App\Services\Search\Processors\Concerns;

/**
 * @template TItem of \Illuminate\Database\Eloquent\Model
 */
trait WithModels {
    /**
     * @var array<int, class-string<TItem>>
     */
    private array $models;

    /**
     * @return array<int, class-string<TItem>>
     */
    public function getModels(): array {
        return $this->models;
    }

    /**
     * @param array<int, class-string<TItem>> $models
     */
    public function setModels(array $models): static {
        $this->models = $models;

        return $this;
    }
}
