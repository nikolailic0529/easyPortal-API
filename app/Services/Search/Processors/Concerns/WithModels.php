<?php declare(strict_types = 1);

namespace App\Services\Search\Processors\Concerns;

use Illuminate\Database\Eloquent\Model;

use function array_merge;

/**
 * @template TItem of Model
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

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'models' => $this->getModels(),
        ]);
    }
}
