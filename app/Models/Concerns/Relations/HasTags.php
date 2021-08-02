<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Concerns\SyncBelongsToMany;
use App\Models\Pivot;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @mixin \App\Models\Model
 */
trait HasTags {
    use SyncBelongsToMany;

    public function tags(): BelongsToMany {
        $pivot = $this->getTagsPivot();

        return $this
            ->belongsToMany(Tag::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Tag>|array<\App\Models\Tag> $tags
     */
    public function setTagsAttribute(Collection|array $tags): void {
        $this->syncBelongsToMany('tags', $tags);
    }

    abstract protected function getTagsPivot(): Pivot;
}
