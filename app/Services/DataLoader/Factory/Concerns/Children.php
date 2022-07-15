<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Utils\Eloquent\Callbacks\KeysComparator;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_shift;
use function count;

trait Children {
    /**
     * @template T of \App\Services\DataLoader\Schema\Type
     * @template M of \App\Utils\Eloquent\Model
     * @template C of \Illuminate\Support\Collection<int, M>
     *
     * @param C                     $existing
     * @param array<T>              $children
     * @param Closure(T): ?M        $factory
     * @param Closure(M, M): int    $compare
     * @param Closure(M): bool|null $isReusable
     *
     * @return C
     */
    protected function children(
        Collection $existing,
        array $children,
        Closure $factory,
        Closure $compare,
        Closure $isReusable = null,
    ): Collection {
        // Often children don't have ID for this reason we were tried to compare
        // them by properties, but there were a lot of create/soft-delete
        // queries anyway. So we are trying to re-use removed entries to
        // reduce the number of queries.
        /** @var Collection<int, M> $created */
        $created  = new Collection();
        $existing = $existing->sort(new KeysComparator());
        $actual   = new ($existing::class)();

        foreach ($children as $child) {
            $child = $factory($child);

            if (!$child) {
                continue;
            }

            $existingKey = $existing->search(static function (Model $model) use ($compare, $child): bool {
                return $compare($model, $child) === 0;
            });

            if ($existingKey !== false) {
                // `forceFill` is used for relations because we need to call
                // mutators to update property value.
                /** @var M $item because we found the key */
                $item = $existing->pull($existingKey);
                $item
                    ->forceFill($child->getAttributes())
                    ->forceFill($child->getRelations());

                $child = $item;
            } else {
                $created->push($child);
            }

            $actual[] = $child;
        }

        // Reuse
        if (!$created->isEmpty() && !$existing->isEmpty()) {
            $reusable = $isReusable
                ? $existing->filter($isReusable)
                : $existing;

            if (!$reusable->isEmpty()) {
                $created  = $created->sort(new KeysComparator())->all();
                $reusable = $reusable->all();

                while (count($created) > 0 && count($reusable) > 0) {
                    $child         = array_shift($created);
                    $child->exists = true;

                    $child->setAttribute(
                        $child->getKeyName(),
                        array_shift($reusable)->getKey(),
                    );
                }
            }
        }

        // Return
        return $actual;
    }
}
