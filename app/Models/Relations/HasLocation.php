<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Location;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasLocation {
    #[CascadeDelete(false)]
    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(Location $location): void {
        $this->location()->associate($location);
    }
}
