<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Jobs\LocationsRecalculate;
use Illuminate\Contracts\Config\Repository;

class RecalculateLocations extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-recalculate-locations',
            '${objects}' => 'locations',
        ];
    }

    public function handle(Repository $config, LocationsRecalculate $job): int {
        return $this->process($config, $job);
    }
}