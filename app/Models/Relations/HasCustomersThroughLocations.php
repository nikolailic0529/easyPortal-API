<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCustomersThroughLocations {
    #[CascadeDelete(false)]
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
