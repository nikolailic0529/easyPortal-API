<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

/**
 * @extends ProcessorCommand<CustomersProcessor>
 */
class CustomersRecalculate extends ProcessorCommand {
    public function __invoke(CustomersProcessor $processor): int {
        return $this->process($processor);
    }
}
