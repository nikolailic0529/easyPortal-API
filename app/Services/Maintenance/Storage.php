<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\JsonStorage;

class Storage extends JsonStorage {
    public function __construct(AppDisk $disc) {
        parent::__construct($disc, 'maintenance.json');
    }
}
