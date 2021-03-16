<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use Closure;
use SplObjectStorage;

use function array_merge;
use function in_array;
use function iterator_to_array;

trait Polymorphic {
    use WithType;

    /**
     * @param array<\App\Services\DataLoader\Schema\Type> $types
     *
     * @return array<mixed>
     */
    private function polymorphic(Model $owner, array $types, Closure $getType, Closure $factory): array {
        // First, we should convert type into the internal model and determine its types.
        /** @var \SplObjectStorage<\App\Models\Contact|\App\Models\Location, array<\App\Models\Type>> $models */
        $models = new SplObjectStorage();

        foreach ($types as $object) {
            // Search contact
            $model = $factory($owner, $object);

            if (!$model) {
                continue;
            }

            // Determine type
            $type = $this->type($model, $getType($object));

            if ($models->contains($model)) {
                if (in_array($type, $models[$model], true)) {
                    $this->logger->warning('Found object with multiple models with the same type.', [
                        'owner'  => $owner,
                        'object' => $object,
                        'model'  => $model,
                        'type'   => $type,
                    ]);
                } else {
                    $models[$model] = array_merge($models[$model], [$type]);
                }
            } else {
                $models[$model] = [$type];
            }
        }

        // Attach types into models
        foreach ($models as $model) {
            $model->types = $models[$model];
        }

        // Return
        return iterator_to_array($models);
    }
}
