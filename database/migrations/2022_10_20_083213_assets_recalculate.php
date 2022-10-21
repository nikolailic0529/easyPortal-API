<?php declare(strict_types = 1);

use App\Models\Asset;
use App\Services\Recalculator\Migrations\Recalculate;

return new class() extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getModels(): array {
        return [
            Asset::class,
        ];
    }
};
