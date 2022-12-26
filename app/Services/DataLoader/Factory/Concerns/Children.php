<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Callbacks\KeysComparator;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SplObjectStorage;

use function assert;

trait Children {
    /**
     * @template T of Type
     * @template M of Model
     * @template C of Collection<array-key,M>|EloquentCollection<array-key,M>
     *
     * @param C                     $existing
     * @param array<?T>             $children
     * @param Closure(M): bool|null $isReusable
     * @param Closure(M|T): string  $keyer
     * @param Closure(T, ?M): ?M    $factory
     *
     * @return C
     */
    protected function children(
        Collection $existing,
        array $children,
        ?Closure $isReusable,
        Closure $keyer,
        Closure $factory,
    ): Collection {
        // Often children don't have ID for this reason we were tried to compare
        // them by properties, but there were a lot of create/soft-delete
        // queries anyway. So we are trying to re-use removed entries to
        // reduce the number of queries.

        // Existing?
        if ($existing->isEmpty()) {
            /** @var C $models */
            $models = new ($existing::class)();

            foreach ($children as $child) {
                if ($child === null) {
                    continue;
                }

                $model = $factory($child, null);

                if ($model !== null) {
                    $models[] = $model;
                }
            }

            return $models;
        }

        // Map children to existing
        /** @var SplObjectStorage<T, ?M> $map */
        $map   = new SplObjectStorage();
        $class = $existing::class;

        if (!$existing->isEmpty()) {
            // There are no guarantees that the key is unique, so `groupBy` is used.
            $existing = $existing->sort(new KeysComparator())->groupBy($keyer);

            foreach ($children as $child) {
                if ($child === null) {
                    continue;
                }

                $key   = $keyer($child);
                $group = $existing[$key] ?? null;
                $model = $group?->shift();

                assert($model === null || $model instanceof Model);

                $map[$child] = $model;
            }

            /** @var Collection<array-key,M> $existing phpstan not so smart yet */
            $existing = $existing->flatten(1);
        }

        // Update
        /** @var C $models */
        $models   = new $class();
        $existing = $isReusable
            ? $existing->filter($isReusable)
            : $existing;

        foreach ($map as $child) {
            $model   = $map[$child] ?? $existing->first();
            $created = $factory($child, $model);

            if ($created !== null) {
                $models[] = $created;

                if ($map[$child] === null) {
                    $existing->shift();
                }
            }
        }

        // Return
        return $models;
    }
}
