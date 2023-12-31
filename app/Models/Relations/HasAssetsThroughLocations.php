<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Models\Data\Location;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin Model
 */
trait HasAssetsThroughLocations {
    /**
     * @return HasManyThrough<Asset>
     */
    public function assets(): HasManyThrough {
        return $this->hasManyThrough(Asset::class, Location::class)->distinct();
    }
}
