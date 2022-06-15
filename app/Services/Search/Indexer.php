<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Queue\Tasks\ModelIndex;
use App\Services\Search\Queue\Tasks\ModelsIndex;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
use function is_a;
use function reset;
use function sort;
use function sprintf;

class Indexer {
    public function __construct(
        private Container $container,
    ) {
        // empty
    }

    /**
     * @param Collection<array-key,Model>|Model|array{model:class-string<Model>,keys:array<string|int>} $models
     */
    public function update(Collection|Model|array $models): void {
        // Models
        $keys  = [];
        $model = null;

        if ($models instanceof Collection) {
            $model = $models->first();
            $model = $model ? $model::class : null;
            $keys  = $models->map(new GetKey())->all();
        } elseif ($models instanceof Model) {
            $model = $models::class;
            $keys  = [$models->getKey()];
        } else {
            $model = $models['model'];
            $keys  = $models['keys'];
        }

        // Empty?
        $keys = array_unique(array_filter(array_values($keys)));

        sort($keys);

        if (!$model || !$keys) {
            return;
        }

        // Searchable?
        if (!is_a($model, Model::class, true) || !is_a($model, Searchable::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Model `%s` is not Searchable.',
                $model,
            ));
        }

        // Dispatch
        if (count($keys) === 1) {
            $this->container->make(ModelIndex::class)
                ->init($model, reset($keys))
                ->dispatch();
        } else {
            $this->container->make(ModelsIndex::class)
                ->init($model, $keys)
                ->dispatch();
        }
    }
}
