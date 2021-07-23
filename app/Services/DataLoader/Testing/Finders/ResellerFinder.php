<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Reseller;
use App\Services\DataLoader\Finders\ResellerFinder as ResellerFinderContract;

class ResellerFinder implements ResellerFinderContract {
    public function find(string $key): ?Reseller {
        return Reseller::factory()->create([
            'id' => $key,
        ]);
    }
}
