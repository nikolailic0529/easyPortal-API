<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Models\Customer;
use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculate<Customer>
 */
class CustomerRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-recalculator-customer-recalculate';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(CustomersProcessor::class);
    }
}
