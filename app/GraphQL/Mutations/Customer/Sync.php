<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Customer;

use App\Models\Customer;
use App\Services\DataLoader\Queue\Tasks\CustomerSync;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @return array{result: bool, warranty: bool}
     */
    public function __invoke(Customer $customer): array {
        $job    = $this->container->make(CustomerSync::class)->init($customer);
        $result = $this->container->call($job);

        return $result;
    }
}
