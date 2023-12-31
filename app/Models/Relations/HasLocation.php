<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Location;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasLocation {
    /**
     * @return BelongsTo<Location, self>
     */
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(Location $location): void {
        $this->location()->associate($location);
    }
}
