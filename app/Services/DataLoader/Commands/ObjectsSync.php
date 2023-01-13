<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizer;
use App\Utils\Processor\Commands\ProcessorCommand;
use App\Utils\Processor\Contracts\Processor;

use function array_merge;

/**
 * @template TProcessor of Synchronizer
 *
 * @extends ProcessorCommand<TProcessor>
 */
abstract class ObjectsSync extends ProcessorCommand {
    /**
     * @inheritDoc
     */
    protected static function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{--from= : Start processing from given DateTime/DateInterval}',
            '{--f|force : Force update}',
            '{--outdated : Process outdated ${objects}}',
            '{--no-outdated : Do not process outdated ${objects} (default)}',
            '{--outdated-limit= : Maximum number of outdated ${objects} to process (default "all")}',
            '{--outdated-expire= : DateTime/DateInterval when ${object} become outdated (default "now")}',
        ]);
    }

    protected function process(Processor $processor): int {
        $from           = $this->getDateTimeOption('from');
        $force          = $this->getFlagOption('force');
        $outdated       = $this->getBoolOption('outdated', false);
        $outdatedLimit  = $this->getIntOption('outdated-limit');
        $outdatedExpire = $this->getDateTimeOption('outdated-expire');
        $processor      = $processor
            ->setFrom($from)
            ->setForce($force)
            ->setWithOutdated($outdated)
            ->setOutdatedLimit($outdatedLimit)
            ->setOutdatedExpire($outdatedExpire);

        return parent::process($processor);
    }
}
