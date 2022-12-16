<?php declare(strict_types = 1);

namespace App\Services\Queue\Utils;

use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
use function reset;
use function sort;

/**
 * @template TModel of Model
 */
abstract class Dispatcher {
    public function __construct() {
        // empty
    }

    /**
     * @param Collection<array-key,TModel>|TModel|array{model:class-string<TModel>,keys:array<string|int>} $models
     */
    public function dispatch(Collection|Model|array $models): void {
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

        if (!$model || !$keys || !$this->isDispatchable($model)) {
            return;
        }

        // Dispatch
        if (count($keys) === 1) {
            $this->dispatchModel($model, reset($keys));
        } else {
            $this->dispatchModels($model, $keys);
        }
    }

    /**
     * @param class-string<TModel> $model
     */
    abstract protected function dispatchModel(string $model, string|int $key): void;

    /**
     * @param class-string<TModel> $model
     * @param array<string|int>    $keys
     */
    abstract protected function dispatchModels(string $model, array $keys): void;

    /**
     * @param class-string<Model> $model
     */
    protected function isDispatchable(string $model): bool {
        return true;
    }
}
