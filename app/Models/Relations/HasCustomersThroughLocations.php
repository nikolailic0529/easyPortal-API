<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Utils\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

/**
 * @mixin Model
 */
trait HasCustomersThroughLocations {
    public function customers(): HasManyDeep {
        return $this->hasManyDeep(
            Customer::class,
            [
                Location::class,
                CustomerLocation::class,
            ],
            [
                null,
                null,
                'id',
            ],
            [
                null,
                null,
                'customer_id',
            ],
        );
    }
}
