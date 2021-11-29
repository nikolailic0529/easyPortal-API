<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ServiceGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasServiceGroup {
    public function serviceGroup(): BelongsTo {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function setServiceGroupAttribute(?ServiceGroup $group): void {
        $this->serviceGroup()->associate($group);
    }
}
