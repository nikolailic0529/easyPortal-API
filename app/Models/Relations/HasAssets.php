<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasAssets {
    public function assets(): HasMany {
        return $this->hasMany(Asset::class);
    }
}
