<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;

interface ServiceLevelFinder {
    public function find(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel;
}
