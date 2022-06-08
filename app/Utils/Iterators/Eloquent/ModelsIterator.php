<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Iterators\ObjectIteratorIterator;
use App\Utils\Iterators\ObjectsIterator;
use Countable;
use Illuminate\Database\Eloquent\Builder;

use function count;

/**
 * Special iterator to iterate Models by the given hundreds and thousands of keys.
 *
 * In some situations (eg while import) we need to iterate so many keys that
 * `whereIn()` will fail. In this case, this iterator is your choice - it split
 * keys into chunks and then load and return models for each chunk. Please note
 * that if model is not exist the `null` will be returned.
 *
 * @template TItem of \Illuminate\Database\Eloquent\Model
 *
 * @extends ObjectIteratorIterator<TItem|null, string|int>
 */
class ModelsIterator extends ObjectIteratorIterator implements Countable {
    /**
     * @param Builder<TItem>         $builder
     * @param array<int, string|int> $keys
     */
    public function __construct(
        protected Builder $builder,
        protected array $keys,
    ) {
        parent::__construct(new ObjectsIterator($this->keys));
    }

    /**
     * @inheritDoc
     */
    protected function chunkConvert(array $items): array {
        $converted = [];

        if ($items) {
            $model  = $this->builder->getModel();
            $models = (clone $this->builder)
                ->whereIn($model->getKeyName(), $items)
                ->get()
                ->keyBy(new GetKey());

            foreach ($items as $key => $item) {
                $converted[$key] = $models->get($item);
            }
        }

        return $converted;
    }

    public function count(): int {
        return count($this->keys);
    }
}
