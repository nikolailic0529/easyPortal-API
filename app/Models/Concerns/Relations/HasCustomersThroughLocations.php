<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Customer;
use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin \App\Models\Model
 */
trait HasCustomersThroughLocations {
    public function customers(): HasManyThrough {
        return $this->hasManyThrough(
            Customer::class,
            Location::class,
            null,
            (new Customer())->getKeyName(),
            null,
            'object_id',
        );
    }
}
