<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\I18n\Formatter;
use App\Services\Recalculator\Processor\Processors\AssetsProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

/**
 * @extends ProcessorCommand<AssetsProcessor>
 */
class AssetsRecalculate extends ProcessorCommand {
    public function __invoke(Formatter $formatter, AssetsProcessor $processor): int {
        return $this->process($formatter, $processor);
    }
}
