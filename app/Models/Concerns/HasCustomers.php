<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasCustomers {
    public function customers(): HasMany {
        return $this->hasMany(Customer::class);
    }
}
