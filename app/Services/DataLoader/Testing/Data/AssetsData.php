<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

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
            Context::OEMS,
        ];
    }

    abstract protected function generateData(string $path, Context $context): bool;
}
