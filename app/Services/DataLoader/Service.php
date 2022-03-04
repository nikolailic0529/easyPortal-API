<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Queues;
use App\Services\Service as BaseService;

class Service extends BaseService {
    public static function getDefaultQueue(): string {
        return Queues::DATA_LOADER;
    }
}
