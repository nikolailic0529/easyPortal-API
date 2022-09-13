<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\I18n\Formatter;
use App\Utils\Processor\Commands\ProcessorCommand;
use App\Utils\Processor\Contracts\Processor;

use function array_merge;

/**
 * @template TProcessor of \App\Services\DataLoader\Importer\Importer
 *
 * @extends ProcessorCommand<TProcessor>
 */
abstract class ObjectsImport extends ProcessorCommand {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--from= : start processing from given DateTime/DateInterval}',
        ]);
    }

    protected function process(Formatter $formatter, Processor $processor): int {
        $from      = $this->getDateTimeOption('from');
        $processor = $processor
            ->setFrom($from);

        return parent::process($formatter, $processor);
    }
}
