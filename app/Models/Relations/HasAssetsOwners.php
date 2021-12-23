<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasAssetsOwners {
    #[CascadeDelete(false)]
    public function assets(): HasMany {
        return $this->hasMany(Asset::class, $this->getKeyName(), 'object_id');
    }
}
