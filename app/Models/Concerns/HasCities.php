<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\City;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasCities {
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}
