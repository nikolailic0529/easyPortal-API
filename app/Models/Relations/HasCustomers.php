<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use function count;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCustomers {
    use SyncBelongsToMany;

    public function customers(): BelongsToMany {
        $pivot = $this->getCustomersPivot();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param array<string,array<string,mixed>> $customers
     */
    public function setCustomersPivotsAttribute(array $customers): void {
        $this->syncBelongsToManyPivots('customers', $customers);
        $this->customers_count = count($customers);
    }

    abstract protected function getCustomersPivot(): Pivot;
}
