<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use App\Models\Document;
use App\Services\Recalculator\Processor\Processors\DocumentsProcessor;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * @extends Recalculator<Document>
 */
class DocumentsRecalculator extends Recalculator {
    public function displayName(): string {
        return 'ep-recalculator-documents-recalculator';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(DocumentsProcessor::class);
    }
}
