<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\I18n\Formatter;
use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

class LocationsRecalculate extends ProcessorCommand {
    public function __invoke(Formatter $formatter, LocationsProcessor $processor): int {
        return $this->process($formatter, $processor);
    }
}
