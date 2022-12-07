<?php declare(strict_types = 1);

namespace App\Services\Audit\Traits;

use App\Services\Audit\Contracts\Auditable;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function array_diff;
use function assert;
use function debug_backtrace;
use function func_get_args;
use function is_a;
use function sort;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * @see Auditable
 *
 * @mixin Model
 */
trait AuditableImpl {
    /**
     * @var array<string, array{type: string, added: array<string|int>, deleted: array<string|int>}>
     */
    private array $dirtyRelations = [];

    /**
     * @return array<string, array{type: string, added: array<string|int>, deleted: array<string|int>}>
     */
    public function getDirtyRelations(): array {
        return $this->dirtyRelations;
    }

    /**
     * @return array<string>
     */
    public function getInternalAttributes(): array {
        return [];
    }

    /**
     * @param string $relation
     *
     * @return $this
     */
    public function setRelation(mixed $relation, mixed $value): mixed {
        // Record
        if ($this->relationLoaded($relation) && $value instanceof Collection && !$this->isEagerLoading()) {
            $existing = $this->getRelation($relation);

            assert($existing instanceof Collection);

            $model    = $value->first() ?? $existing->first();
            $mapper   = new GetKey();
            $existing = $existing->map($mapper)->all();
            $current  = $value->map($mapper)->all();
            $deleted  = array_diff($existing, $current);
            $added    = array_diff($current, $existing);

            if ($model instanceof Model && (!!$added || !!$deleted)) {
                sort($deleted);
                sort($added);

                $this->dirtyRelations[$relation] = [
                    'type'    => $model->getMorphClass(),
                    'added'   => $added,
                    'deleted' => $deleted,
                ];
            }
        }

        // Parent
        return parent::setRelation($relation, $value);
    }

    public function syncOriginal(): mixed {
        // Reset
        $this->dirtyRelations = [];

        // Parent
        return parent::syncOriginal();
    }

    /**
     * @param array<mixed>|string|null $attributes
     */
    public function isDirty(mixed $attributes = null): mixed {
        // @phpstan-ignore-next-line Laravel's type is incorrect ¯\_(ツ)_/¯
        return parent::isDirty(...func_get_args())
            || ($attributes === null && !!$this->dirtyRelations);
    }

    private function isEagerLoading(): bool {
        // The problem is Laravel set eager relations to empty Collection first
        // and then set them to the loaded value. It is breaks out change
        // detection for relations :( So we are looking into the stacktrace and
        // ignore changes from eager loading.
        //
        // Of course, it is not the best way, and would be good to find a better
        // one. Another edge case, changes from the `setRelations()` are ignored
        // now.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $eager = Arr::first($trace, static function (array $call): bool {
            return $call['function'] === 'eagerLoadRelation'
                && is_a($call['class'], Builder::class, true);
        });

        return !!$eager;
    }
}
