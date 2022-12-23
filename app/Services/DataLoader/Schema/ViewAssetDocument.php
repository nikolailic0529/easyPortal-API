<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Utils\JsonObject\JsonObjectNormalizer;

class ViewAssetDocument extends Type {
    public ?string $serviceGroupSku;
    public ?string $serviceGroupSkuDescription;
    public ?string $serviceLevelSku;
    public ?string $serviceLevelSkuDescription;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $serviceFullDescription;

    public ?string       $documentNumber;
    public ?ViewDocument $document;
    public ?string       $startDate;
    public ?string       $endDate;
    public ?string       $deletedAt;

    public ?ViewCompany $reseller;
    public ?ViewCompany $customer;
}
