<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasCustomerNullable {
    /**
     * @return BelongsTo<Customer, self>
     */
    public function customer(): BelongsTo {
        return $this
            ->belongsTo(Customer::class)
            ->withoutGlobalScope(OwnedByScope::class);
    }

    public function setCustomerAttribute(?Customer $customer): void {
        $this->customer()->associate($customer);
    }
}
