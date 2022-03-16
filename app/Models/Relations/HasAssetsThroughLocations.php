<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Models\Location;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin Model
 */
trait HasAssetsThroughLocations {
    #[CascadeDelete(false)]
    public function assets(): HasManyThrough {
        return $this->hasManyThrough(Asset::class, Location::class);
    }
}
