<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Customer;
use App\Models\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @mixin \App\Models\Model
 *
 * @property int $customers_count
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

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Customer>|array<\App\Models\Customer> $customers
     */
    public function setCustomersAttribute(Collection|array $customers): void {
        $this->syncBelongsToMany('customers', $customers);
        $this->customers_count = count($customers);
    }

    abstract protected function getCustomersPivot(): Pivot;
}
