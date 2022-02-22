<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\ResellersProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

class ResellersRecalculate extends ProcessorCommand {
    public function __invoke(ResellersProcessor $processor): int {
        return $this->process($processor);
    }
}
