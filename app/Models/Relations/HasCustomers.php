<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;

use function count;

/**
 * @template TPivot of \App\Utils\Eloquent\Pivot
 *
 * @property-read Collection<int, Customer> $customers
 * @property BaseCollection<string, TPivot> $customersPivots
 *
 * @mixin Model
 */
trait HasCustomers {
    use SyncBelongsToMany;

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return BelongsToMany<Customer>
     */
    public function customers(): BelongsToMany {
        $pivot = $this->getCustomersPivot();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @return HasMany<TPivot>
     */
    #[CascadeDelete]
    public function customersPivots(): HasMany {
        $customers = $this->customers();
        $relation  = $this->hasMany(
            $customers->getPivotClass(),
            $customers->getForeignPivotKeyName(),
        );

        return $relation;
    }

    /**
     * @param array<string,TPivot>|BaseCollection<string,TPivot> $customers
     */
    public function setCustomersPivotsAttribute(BaseCollection|array $customers): void {
        $this->syncBelongsToManyPivots('customers', $customers);
        $this->customers_count = count($customers);
    }

    /**
     * @return TPivot
     */
    abstract protected function getCustomersPivot(): Pivot;
    // </editor-fold>
}
