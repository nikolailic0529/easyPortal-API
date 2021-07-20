<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Oem;
use App\Services\DataLoader\Finders\OemFinder as OemFinderContract;

class OemFinder implements OemFinderContract {
    public function find(string $key): ?Oem {
        return Oem::factory()->create([
            'key' => $key,
        ]);
    }
}
