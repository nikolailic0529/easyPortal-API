<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use App\Models\Customer;

/**
 * Updates search index for Customers.
 */
class CustomersIndexer extends Indexer {
    public function displayName(): string {
        return 'ep-search-customers-indexer';
    }

    protected function getModel(): string {
        return Customer::class;
    }
}
