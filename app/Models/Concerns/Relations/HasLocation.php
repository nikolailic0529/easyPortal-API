<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasLocation {
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(Location $location): void {
        $this->location()->associate($location);
    }
}
