<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Reseller;
use App\Services\DataLoader\Loaders\ResellerLoader;

class ResellerLoaderFinder extends Finder implements ResellerFinder {
    public function find(string $key): ?Reseller {
        return $this->container->make(ResellerLoader::class)->create($key);
    }
}
