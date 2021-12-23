<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\City;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCities {
    #[CascadeDelete(true)]
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}
