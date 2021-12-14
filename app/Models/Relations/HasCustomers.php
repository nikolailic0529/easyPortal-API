<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Support\Collection;

use function count;

/**
 * @template TPivot of \App\Utils\Eloquent\Pivot
 *
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
     * @param array<string,TPivot>|\Illuminate\Support\Collection<string,TPivot> $customers
     */
    public function setCustomersPivotsAttribute(Collection|array $customers): void {
        $this->syncBelongsToManyPivots('customers', $customers);
        $this->customers_count = count($customers);
    }

    /**
     * @return TPivot
     */
    abstract protected function getCustomersPivot(): Pivot;
}
