<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Distributor;
use App\Services\DataLoader\Finders\DistributorFinder as DistributorFinderContract;

class DistributorFinder implements DistributorFinderContract {
    public function find(string $key): ?Distributor {
        return Distributor::factory()->create([
            'id' => $key,
        ]);
    }
}
