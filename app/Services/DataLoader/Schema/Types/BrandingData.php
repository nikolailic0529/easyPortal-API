<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Normalizers\ColorNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
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

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $defaultLogoUrl;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $favIconUrl;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $useDefaultFavIcon;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $resellerAnalyticsCode;

    #[JsonObjectNormalizer(BoolNormalizer::class)]
    public ?bool $brandingMode;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $mainImageOnTheRight;

    #[JsonObjectNormalizer(StringNormalizer::class)]
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
