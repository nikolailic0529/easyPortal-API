<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Customer;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasCustomer {
    use HasCustomerNullable {
        setCustomerAttribute as private setCustomerAttributeNullable;
    }

    public function setCustomerAttribute(Customer $customer): void {
        $this->setCustomerAttributeNullable($customer);
    }
}
