<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasAssetsThroughLocations {
    public function assets(): HasManyThrough {
        return $this->hasManyThrough(Asset::class, Location::class);
    }
}