<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

use function count;

/**
 * @template TPivot of \App\Utils\Eloquent\Pivot
 *
 * @property Collection<string, TPivot> $customersPivots
 *
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasCustomers {
    use SyncBelongsToMany;

    #[CascadeDelete(false)]
    public function customers(): BelongsToMany {
        $pivot = $this->getCustomersPivot();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    #[CascadeDelete(true)]
    public function customersPivots(): HasMany {
        $customers = $this->customers();
        $relation  = $this->hasMany(
            $customers->getPivotClass(),
            $customers->getForeignPivotKeyName(),
        );

        return $relation;
    }

    /**
     * @param array<string,TPivot>|Collection<string,TPivot> $customers
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
