<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ServiceLevel;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasServiceLevel {
    #[CascadeDelete(false)]
    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    public function setServiceLevelAttribute(?ServiceLevel $level): void {
        $this->serviceLevel()->associate($level);
    }
}
