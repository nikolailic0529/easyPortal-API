<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Importer;

use App\Utils\Processor\State;
use DateTimeInterface;

class UsersImporterState extends State {
    public ?DateTimeInterface $started = null;
    public bool               $overall = false;
}
