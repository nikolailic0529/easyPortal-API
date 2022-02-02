<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Utils\Processor\State;
use DateTimeInterface;

class UsersImporterState extends State {
    public DateTimeInterface $started;
    public bool              $overall;
}
