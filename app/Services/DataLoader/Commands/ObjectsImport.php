<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\I18n\Formatter;
use App\Utils\Console\WithBooleanOptions;
use App\Utils\Processor\Commands\ProcessorCommand;
use App\Utils\Processor\Processor;
use Illuminate\Support\Facades\Date;

use function array_merge;

abstract class ObjectsImport extends ProcessorCommand {
    use WithBooleanOptions;

    /**
     * @inheritDoc
     */
    protected function getCommandOptions(): array {
        return array_merge(parent::getCommandOptions(), [
            '{--u|update : Update ${objects} if exists (default)}',
            '{--U|no-update : Do not update ${objects} if exists}',
            '{--from= : start processing from given datetime}',
        ]);
    }

    protected function process(Formatter $formatter, Processor $processor): int {
        $from      = $this->hasOption('from') ? Date::make($this->option('from')) : null;
        $update    = $this->getBooleanOption('update', true);
        $processor = $processor
            ->setUpdate($update)
            ->setFrom($from);

        return parent::process($formatter, $processor);
    }
}
