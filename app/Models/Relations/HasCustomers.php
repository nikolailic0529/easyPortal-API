<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Models\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin \App\Models\Model
 */
trait HasCustomers {
    public function customers(): BelongsToMany {
        $pivot = $this->getCustomersPivot();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    abstract protected function getCustomersPivot(): Pivot;
}
