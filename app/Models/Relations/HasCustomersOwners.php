<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCustomersOwners {
    public function customers(): HasMany {
        return $this->hasMany(Customer::class, $this->getKeyName(), 'object_id');
    }
}
