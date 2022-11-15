<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Type;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin Model
 */
trait HasTypes {
    use SyncBelongsToMany;

    /**
     * @return BelongsToMany<Type>
     */
    public function types(): BelongsToMany {
        $pivot = $this->getTypesPivot();

        return $this
            ->belongsToMany(Type::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param Collection<int,Type> $types
     */
    public function setTypesAttribute(Collection $types): void {
        $this->syncBelongsToMany('types', $types);
    }

    abstract protected function getTypesPivot(): Pivot;
}
