<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\City;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
trait HasCities {
    /**
     * @return HasMany<City>
     */
    #[CascadeDelete]
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}
