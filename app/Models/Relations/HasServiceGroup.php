<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\ServiceGroup;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasServiceGroup {
    /**
     * @return BelongsTo<ServiceGroup, self>
     */
    public function serviceGroup(): BelongsTo {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function setServiceGroupAttribute(?ServiceGroup $group): void {
        $this->serviceGroup()->associate($group);
    }
}
