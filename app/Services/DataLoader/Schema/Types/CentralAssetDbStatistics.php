<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\IntNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CentralAssetDbStatistics extends Type {
    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $assetsAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $documentsAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $documentsContractAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $documentsQuoteAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $companiesAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $companiesResellerAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $companiesCustomerAmount;

    #[JsonObjectNormalizer(IntNormalizer::class)]
    public ?int $companiesDistributorAmount;
}
