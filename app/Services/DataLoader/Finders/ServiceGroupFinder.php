<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;

interface ServiceGroupFinder {
    public function find(Oem $oem, string $sku): ?ServiceGroup;
}
