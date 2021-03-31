<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin \App\Models\Model
 */
trait HasTypes {
    use SyncBelongsToMany;

    public function types(): BelongsToMany {
        return $this->belongsToMany(Type::class, Str::singular($this->getTable()).'_types')->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Type>|array<\App\Models\Type> $types
     */
    public function setTypesAttribute(Collection|array $types): void {
        $this->syncBelongsToMany('types', $types);
    }
}
