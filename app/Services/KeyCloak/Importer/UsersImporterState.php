<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Utils\Processor\State;

class UsersImporterState extends State {
    public bool $overall = false;
}
