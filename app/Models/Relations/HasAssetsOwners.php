<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasAssetsOwners {
    public function assets(): HasMany {
        return $this->hasMany(Asset::class, $this->getKeyName(), 'object_id');
    }
}
