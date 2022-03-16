<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasCustomerNullable {
    #[CascadeDelete(false)]
    public function customer(): BelongsTo {
        return $this
            ->belongsTo(Customer::class)
            ->withoutGlobalScope(OwnedByOrganizationScope::class);
    }

    public function setCustomerAttribute(?Customer $customer): void {
        $this->customer()->associate($customer);
    }
}
