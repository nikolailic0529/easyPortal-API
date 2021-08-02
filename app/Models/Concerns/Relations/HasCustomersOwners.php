<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasCustomersOwners {
    public function customers(): HasMany {
        return $this->hasMany(Customer::class, $this->getKeyName(), 'object_id');
    }
}
