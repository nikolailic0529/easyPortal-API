<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

/**
 * @extends ProcessorCommand<LocationsProcessor>
 */
class LocationsRecalculate extends ProcessorCommand {
    public function __invoke(LocationsProcessor $processor): int {
        return $this->process($processor);
    }
}
