<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Contact;
use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Models\Data\Type;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Schema\Type as SchemaType;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use SplObjectStorage;

use function array_merge;
use function array_unique;

use const SORT_REGULAR;

trait Polymorphic {
    use WithType;

    /**
     * @template O of Model
     * @template T of SchemaType
     * @template M of Contact|Location|CustomerLocation|ResellerLocation
     *
     * @param O                   $owner
     * @param array<T>            $objects
     * @param Closure(T): ?string $getType
     * @param Closure(O, T): ?M   $factory
     *
     * @return Collection<array-key, M>
     */
    private function polymorphic(Model $owner, array $objects, Closure $getType, Closure $factory): Collection {
        // First, we should convert type into the internal model and determine its types.
        /** @var SplObjectStorage<M, array<Type>> $models */
        $models = new SplObjectStorage();

        foreach ($objects as $object) {
            // Search contact
            $model = $factory($owner, $object);

            if (!$model) {
                continue;
            }

            // Type defined?
            $type = $getType($object);

            if ($type === null || $type === '') {
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
            $model->types = Collection::make(array_unique($models[$model], SORT_REGULAR));
        }

        // Return
        /** @var Collection<array-key, M> $items */
        $items = new Collection($models);

        return $items;
    }
}
