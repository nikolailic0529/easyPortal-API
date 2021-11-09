<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait Children {
    /**
     * @template T of \App\Services\DataLoader\Schema\Type
     * @template M of \App\Models\Model
     * @template C of \Illuminate\Support\Collection<M>
     *
     * @param C                   $existing
     * @param array<T>            $children
     * @param \Closure(T): ?M     $factory
     * @param \Closure(M, M): int $compare
     *
     * @return C
     */
    protected function children(Collection $existing, array $children, Closure $factory, Closure $compare): Collection {
        // Often children don't have ID for this reason we were tried to compare
        // them by properties, but there were a lot of create/soft-delete
        // queries anyway. So we are trying to re-use removed entries to
        // reduce the number of queries.
        $sort     = static function (Model $a, Model $b): int {
            return $a->getKey() <=> $b->getKey();
        };
        $existing = $existing->sort($sort);
        $created  = new Collection();
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
                $child = $existing
                    ->pull($existingKey)
                    ->forceFill($child->getAttributes())
                    ->forceFill($child->getRelations());
            } else {
                $created->push($child);
            }

            $actual[] = $child;
        }

        // Reuse
        if (!$created->isEmpty() && !$existing->isEmpty()) {
            $created = $created->sort($sort);

            while (!$created->isEmpty() && !$existing->isEmpty()) {
                $child                         = $created->shift();
                $child->exists                 = true;
                $child->{$child->getKeyName()} = $existing->shift()->getKey();
            }
        }

        // Return
        return $actual;
    }
}
