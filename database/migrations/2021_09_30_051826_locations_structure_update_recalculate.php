<?php declare(strict_types = 1);

use App\Models\Customer;
use App\Models\Location;
use App\Models\Reseller;
use App\Services\Recalculator\Migrations\Recalculate;

return new class() extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [
            Reseller::class,
            Customer::class,
            Location::class,
        ];
    }
};
