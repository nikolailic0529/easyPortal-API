<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use LastDragon_ru\LaraASP\Testing\Utils\TestData;

abstract class AssetsData extends Data {
    /**
     * @inheritdoc
     */
    protected function getSupporterContext(): array {
        return [
            Context::DISTRIBUTORS,
            Context::RESELLERS,
            Context::CUSTOMERS,
            Context::TYPES,
        ];
    }

    abstract protected function generateData(TestData $root, Context $context): bool;
}
