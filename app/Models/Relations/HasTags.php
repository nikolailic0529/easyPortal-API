<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Tag;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin Model
 */
trait HasTags {
    use SyncBelongsToMany;

    /**
     * @return BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany {
        $pivot = $this->getTagsPivot();

        return $this
            ->belongsToMany(Tag::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param Collection<int,Tag> $tags
     */
    public function setTagsAttribute(Collection $tags): void {
        $this->syncBelongsToMany('tags', $tags);
    }

    abstract protected function getTagsPivot(): Pivot;
}
