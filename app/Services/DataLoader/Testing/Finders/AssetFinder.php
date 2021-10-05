<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Asset;
use App\Services\DataLoader\Finders\AssetFinder as AssetFinderContract;

class AssetFinder implements AssetFinderContract {
    public function find(string $key): ?Asset {
        return Asset::factory()->create([
            'id' => $key,
        ]);
    }
}
