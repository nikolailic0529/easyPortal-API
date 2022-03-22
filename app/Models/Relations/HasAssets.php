<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Asset;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
trait HasAssets {
    #[CascadeDelete(false)]
    public function assets(): HasMany {
        return $this->hasMany(Asset::class);
    }
}
