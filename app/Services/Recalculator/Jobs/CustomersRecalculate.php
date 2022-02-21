<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends \App\Services\Recalculator\Jobs\Recalculate<\App\Models\Customer>
 */
class CustomersRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-recalculator-customers-recalculate';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(CustomersProcessor::class);
    }
}
