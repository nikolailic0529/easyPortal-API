<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\ColorNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;

class BrandingData extends Type {
    #[JsonObjectNormalizer(ColorNormalizer::class)]
    public ?string $mainColor;

    #[JsonObjectNormalizer(ColorNormalizer::class)]
    public ?string $secondaryColor;

    #[JsonObjectNormalizer(ColorNormalizer::class)]
    public ?string $defaultMainColor;

    #[JsonObjectNormalizer(ColorNormalizer::class)]
    public ?string $secondaryColorDefault;

    public ?string $defaultLogoUrl;
    public ?string $favIconUrl;
    public ?string $useDefaultFavIcon;
    public ?string $resellerAnalyticsCode;

    #[JsonObjectNormalizer(BoolNormalizer::class)]
    public ?bool $brandingMode;

    public ?string $mainImageOnTheRight;
    public ?string $logoUrl;

    /**
     * @var array<TranslationText>|null
     */
    #[JsonObjectArray(TranslationText::class)]
    public ?array $mainHeadingText = null;

    /**
     * @var array<TranslationText>|null
     */
    #[JsonObjectArray(TranslationText::class)]
    public ?array $underlineText = null;
}
