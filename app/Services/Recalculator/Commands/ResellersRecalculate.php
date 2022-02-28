<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\I18n\Formatter;
use App\Services\Recalculator\Processor\Processors\ResellersProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

class ResellersRecalculate extends ProcessorCommand {
    public function __invoke(Formatter $formatter, ResellersProcessor $processor): int {
        return $this->process($formatter, $processor);
    }
}
