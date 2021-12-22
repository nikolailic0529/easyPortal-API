<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasAsset {
    #[CascadeDelete(false)]
    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class);
    }

    public function setAssetAttribute(Asset $asset): void {
        $this->asset()->associate($asset);
    }
}
