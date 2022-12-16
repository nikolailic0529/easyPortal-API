<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Commands;

use App\Services\Keycloak\Importer\UsersImporter;
use App\Utils\Processor\Commands\ProcessorCommand;

/**
 * @extends ProcessorCommand<UsersImporter>
 */
class UsersSync extends ProcessorCommand {
    public function __invoke(UsersImporter $importer): int {
        return $this->process($importer);
    }
}
