<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasAssets {
    public function assets(): HasMany {
        return $this->hasMany(Asset::class);
    }
}
