<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Utils\Iterators\OffsetBasedObjectIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Builder;

use function array_slice;

/**
 * Special iterator to iterate Models by the given hundreds and thousands of keys.
 *
 * In some situations (eg while import) we need to iterate so many keys that
 * `whereIn()` will fail. In this case, this iterator is your choice - it split
 * keys into chunks and then load and return models for each chunk. Please note
 * that the order of models inside each chunk is undefined unless you specified
 * it for the Builder.
 *
 * @template T of \Illuminate\Database\Eloquent\Model
 * @template V of string
 *
 * @extends \App\Utils\Iterators\OffsetBasedObjectIterator<T,V>
 */
class ModelsIterator extends OffsetBasedObjectIterator {
    /**
     * @param \Illuminate\Database\Eloquent\Builder<T> $builder
     * @param array<int, V>                            $keys
     */
    public function __construct(
        ExceptionHandler $exceptionHandler,
        protected Builder $builder,
        protected array $keys,
    ) {
        parent::__construct(
            $exceptionHandler,
            function (array $variables): array {
                return array_slice($this->keys, $variables['offset'], $variables['limit']);
            },
        );
    }

    /**
     * @inheritDoc
     */
    protected function chunkConvert(array $items): array {
        $models = [];

        if ($items) {
            $model  = $this->builder->getModel();
            $models = (clone $this->builder)
                ->whereIn($model->getKeyName(), $items)
                ->get()
                ->all();
        }

        return $models;
    }
}
