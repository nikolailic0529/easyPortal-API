<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Processor\Processors\DocumentsProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;

/**
 * @extends ProcessorCommand<DocumentsProcessor>
 */
class DocumentsRecalculate extends ProcessorCommand {
    public function __invoke(DocumentsProcessor $processor): int {
        return $this->process($processor);
    }
}
