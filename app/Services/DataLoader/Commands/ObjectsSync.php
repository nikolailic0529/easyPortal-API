<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\I18n\Formatter;
use App\Utils\Processor\Commands\ProcessorCommand;
use App\Utils\Processor\Contracts\Processor;

use function array_merge;

/**
 * @template TProcessor of \App\Services\DataLoader\Synchronizer\Synchronizer
 *
 * @extends ProcessorCommand<TProcessor>
 */
abstract class ObjectsSync extends ProcessorCommand {
    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--from= : Start processing from given DateTime/DateInterval}',
            '{--outdated : Process outdated ${objects} (default)}',
            '{--no-outdated : Do not process outdated ${objects}}',
            '{--outdated-limit= : Maximum number of outdated ${objects} to process (default "all")}',
            '{--outdated-expire= : DateTime/DateInterval when ${object} become outdated (default "now")}',
        ]);
    }

    protected function process(Formatter $formatter, Processor $processor): int {
        $from           = $this->getDateTimeOption('from');
        $outdated       = $this->getBoolOption('outdated', true);
        $outdatedLimit  = $this->getIntOption('outdated-limit');
        $outdatedExpire = $this->getDateTimeOption('outdated-expire');
        $processor      = $processor
            ->setFrom($from)
            ->setWithOutdated($outdated)
            ->setOutdatedLimit($outdatedLimit)
            ->setOutdatedExpire($outdatedExpire);

        return parent::process($formatter, $processor);
    }
}
