<?php declare(strict_types = 1);

namespace App\Services\Search\Processors\Concerns;

use Illuminate\Database\Eloquent\Model;

use function array_merge;

/**
 * @template TItem of Model
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

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'model' => $this->getModel(),
        ]);
    }
}
