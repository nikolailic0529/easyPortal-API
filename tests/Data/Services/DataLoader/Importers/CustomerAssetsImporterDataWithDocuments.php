<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

class CustomerAssetsImporterDataWithDocuments extends CustomerAssetsImporterDataWithoutDocuments {
    public const LIMIT     = 5;
    public const CUSTOMER  = '019a3b56-b701-4599-8452-2cf9f1f54b26';
    public const DOCUMENTS = true;
}
