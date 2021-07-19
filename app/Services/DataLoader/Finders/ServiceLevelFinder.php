<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Container\Isolated;

interface ServiceLevelFinder extends Isolated {
    public function find(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel;
}
