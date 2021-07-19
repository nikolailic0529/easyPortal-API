<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Container\Isolated;

interface ServiceGroupFinder extends Isolated {
    public function find(Oem $oem, string $sku): ?ServiceGroup;
}
