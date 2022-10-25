<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

abstract class DocumentsData extends AssetsData {
    /**
     * @inheritdoc
     */
    protected function getSupporterContext(): array {
        return [
            Context::DISTRIBUTORS,
            Context::RESELLERS,
            Context::CUSTOMERS,
            Context::ASSETS,
            Context::TYPES,
            Context::OEMS,
        ];
    }
}
