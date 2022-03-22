<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ServiceGroup;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasServiceGroup {
    #[CascadeDelete(false)]
    public function serviceGroup(): BelongsTo {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function setServiceGroupAttribute(?ServiceGroup $group): void {
        $this->serviceGroup()->associate($group);
    }
}
