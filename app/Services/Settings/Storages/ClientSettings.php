<?php declare(strict_types = 1);

namespace App\Services\Settings\Storages;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\JsonStorage;

class ClientSettings extends JsonStorage {
    public function __construct(ClientDisk $disc) {
        parent::__construct($disc, 'settings.json');
    }
}
