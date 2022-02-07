<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Utils\Eloquent\Model;
use Closure;
use SplObjectStorage;

use function array_merge;
use function array_unique;
use function iterator_to_array;

use const SORT_REGULAR;

trait Polymorphic {
    use WithType;

    /**
     * @template T of \App\Utils\Eloquent\Model
     * @template R of \App\Models\Contact|\App\Models\Location
     *
     * @param T                                                     $owner
     * @param array<\App\Services\DataLoader\Schema\Type>           $objects
     * @param \Closure(): \App\Services\DataLoader\Schema\Type      $getType
     * @param \Closure(T, \App\Services\DataLoader\Schema\Type): ?R $factory
     *
     * @return array<R>
     */
    private function polymorphic(Model $owner, array $objects, Closure $getType, Closure $factory): array {
        // First, we should convert type into the internal model and determine its types.
        /** @var \SplObjectStorage<\App\Models\Contact|\App\Models\Location, array<\App\Models\Type>> $models */
        $models = new SplObjectStorage();

        foreach ($objects as $object) {
            // Search contact
            $model = $factory($owner, $object);

            if (!$model) {
                continue;
            }

            // Type defined?
            $type = $getType($object);

            if (!$type) {
                $models[$model] = [];

                continue;
            }

            // Determine type
            $type = $this->type($model, $type);

            if ($models->contains($model)) {
                $models[$model] = array_merge($models[$model], [$type]);
            } else {
                $models[$model] = [$type];
            }
        }

        // Attach types into models
        foreach ($models as $model) {
            $model->types = array_unique($models[$model], SORT_REGULAR);
        }

        // Return
        return iterator_to_array($models);
    }
}
