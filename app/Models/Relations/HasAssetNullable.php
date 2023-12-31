<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasAssetNullable {
    /**
     * @return BelongsTo<Asset, self>
     */
    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class);
    }

    public function setAssetAttribute(?Asset $asset): void {
        $this->asset()->associate($asset);
    }
}
