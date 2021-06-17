<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;

trait CalculatedProperties {
    protected function updateCustomerCalculatedProperties(Customer $customer): void {
        $customer->assets_count = $customer->assets()->count();
        $customer->save();
    }

    protected function updateResellerCalculatedProperties(Reseller $reseller): void {
        $assetsCustomers   = Asset::query()
            ->toBase()
            ->distinct()
            ->select('customer_id')
            ->where('reseller_id', '=', $reseller->getKey());
        $documentsCustomer = Document::query()
            ->toBase()
            ->distinct()
            ->select('customer_id')
            ->where('reseller_id', '=', $reseller->getKey());
        $ids               = $assetsCustomers
            ->union($documentsCustomer)
            ->get()
            ->pluck('customer_id');
        $customers         = Customer::query()
            ->whereIn((new Customer())->getKeyName(), $ids)
            ->get();

        $reseller->customers    = $customers;
        $reseller->assets_count = $reseller->assets()->count();
        $reseller->save();
    }
}
