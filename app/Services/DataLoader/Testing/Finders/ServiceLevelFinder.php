<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Finders\ServiceLevelFinder as ServiceLevelFinderContract;

class ServiceLevelFinder implements ServiceLevelFinderContract {
    public function find(Oem $oem, ServiceGroup $group, string $sku): ?ServiceLevel {
        return ServiceLevel::factory()->create([
            'oem_id'           => $oem,
            'service_group_id' => $group,
            'sku'              => $sku,
        ]);
    }
}
