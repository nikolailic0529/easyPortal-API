<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loader;
use App\Utils\Processor\Commands\ProcessorCommand;
use App\Utils\Processor\Contracts\Processor;

use function array_merge;

/**
 * @template TProcessor of Loader
 *
 * @extends ProcessorCommand<TProcessor>
 */
abstract class ObjectSync extends ProcessorCommand {
    /**
     * @inheritDoc
     */
    protected static function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{id : ${objects} ID}',
            '{--f|force : Force update}',
        ]);
    }

    protected function process(Processor $processor): int {
        $force     = $this->getFlagOption('force');
        $object    = $this->getIdArgument('id');
        $processor = $processor
            ->setObjectId($object)
            ->setForce($force);

        return parent::process($processor);
    }
}
