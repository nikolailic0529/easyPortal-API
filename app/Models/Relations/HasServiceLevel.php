<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ServiceLevel;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasServiceLevel {
    /**
     * @return BelongsTo<ServiceLevel, self>
     */
    #[CascadeDelete(false)]
    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    public function setServiceLevelAttribute(?ServiceLevel $level): void {
        $this->serviceLevel()->associate($level);
    }
}
