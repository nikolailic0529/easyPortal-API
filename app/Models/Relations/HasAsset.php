<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasAsset {
    use HasAssetNullable {
        setAssetAttribute as private setAssetAttributeNullable;
    }

    public function setAssetAttribute(Asset $reseller): void {
        $this->setAssetAttributeNullable($reseller);
    }
}
