<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\ServiceLevel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasServiceLevel {
    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    public function setServiceLevelAttribute(?ServiceLevel $level): void {
        $this->serviceLevel()->associate($level);
    }
}
