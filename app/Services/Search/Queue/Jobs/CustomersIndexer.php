<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Jobs;

use App\Models\Customer;

/**
 * Updates search index for Customers.
 *
 * @extends Indexer<Customer>
 */
class CustomersIndexer extends Indexer {
    public function displayName(): string {
        return 'ep-search-customers-indexer';
    }

    protected function getModel(): string {
        return Customer::class;
    }
}
