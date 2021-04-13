<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\JsonStorage;

class Storage extends JsonStorage {
    public function __construct(AppDisk $disk) {
        parent::__construct($disk, 'settings.json');
    }
}
