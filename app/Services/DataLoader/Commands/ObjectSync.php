<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loader;
use App\Services\I18n\Formatter;
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
    protected function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{id : ${objects} ID}',
        ]);
    }

    protected function process(Formatter $formatter, Processor $processor): int {
        $processor = $processor
            ->setObjectId($this->getIdArgument('id'));

        return parent::process($formatter, $processor);
    }
}
