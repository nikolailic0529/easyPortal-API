<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Services\I18n\Formatter;
use App\Services\KeyCloak\Importer\UsersImporter;
use App\Utils\Processor\Commands\ProcessorCommand;

class UsersSync extends ProcessorCommand {
    public function __invoke(Formatter $formatter, UsersImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
