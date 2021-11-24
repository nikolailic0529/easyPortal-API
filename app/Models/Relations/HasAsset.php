<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasAsset {
    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class);
    }

    public function setAssetAttribute(Asset $asset): void {
        $this->asset()->associate($asset);
    }
}
