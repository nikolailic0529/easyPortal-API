<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Finders\ServiceGroupFinder as ServiceGroupFinderContract;

class ServiceGroupFinder implements ServiceGroupFinderContract {
    public function find(Oem $oem, string $sku): ?ServiceGroup {
        return ServiceGroup::factory()->create([
            'oem_id' => $oem,
            'sku'    => $sku,
        ]);
    }
}
