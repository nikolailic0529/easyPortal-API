<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasCustomerNullable {
    public function customer(): BelongsTo {
        return $this
            ->belongsTo(Customer::class)
            ->withoutGlobalScope(OwnedByOrganizationScope::class);
    }

    public function setCustomerAttribute(?Customer $customer): void {
        $this->customer()->associate($customer);
    }
}
