<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Type;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasTypes {
    use SyncBelongsToMany;

    public function types(): BelongsToMany {
        $pivot = $this->getTypesPivot();

        return $this
            ->belongsToMany(Type::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Type>|array<\App\Models\Type> $types
     */
    public function setTypesAttribute(Collection|array $types): void {
        $this->syncBelongsToMany('types', $types);
    }

    abstract protected function getTypesPivot(): Pivot;
}
