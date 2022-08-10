<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Utils\Eloquent\Callbacks\KeysComparator;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SplObjectStorage;

use function array_shift;
use function is_bool;

trait Children {
    /**
     * @template T of \App\Services\DataLoader\Schema\Type
     * @template M of \Illuminate\Database\Eloquent\Model
     * @template C of \Illuminate\Support\Collection<array-key,M>|\Illuminate\Database\Eloquent\Collection<array-key,M>
     *
     * @param C                   $existing
     * @param array<?T>           $children
     * @param Closure(T, M): bool $comparator
     * @param Closure(T, ?M): ?M  $factory
     *
     * @return C
     */
    protected function children(
        Collection $existing,
        array $children,
        Closure $comparator,
        Closure $factory,
    ): Collection {
        // Often children don't have ID for this reason we were tried to compare
        // them by properties, but there were a lot of create/soft-delete
        // queries anyway. So we are trying to re-use removed entries to
        // reduce the number of queries.

        // Map children to existing
        /** @var SplObjectStorage<T, ?M> $map */
        $map      = new SplObjectStorage();
        $existing = $existing->sort(new KeysComparator());

        foreach ($children as $child) {
            if ($child === null) {
                continue;
            }

            $key         = $existing->search(static function (Model $model) use ($comparator, $child): bool {
                return $comparator($child, $model);
            });
            $map[$child] = null;

            if (!is_bool($key)) {
                $map[$child] = $existing->get($key);

                $existing->forget($key);
            }
        }

        // Reuse
        if (!$existing->isEmpty()) {
            $reusable = $existing->all();

            foreach ($map as $child) {
                if ($map[$child] !== null) {
                    continue;
                }

                if ($reusable) {
                    $map[$child] = array_shift($reusable);
                } else {
                    break;
                }
            }
        }

        // Update
        /** @var C $models */
        $models = new ($existing::class)();

        foreach ($map as $child) {
            $created = $factory($child, $map[$child]);

            if ($created !== null) {
                $models[] = $created;
            }
        }

        // Return
        return $models;
    }
}
