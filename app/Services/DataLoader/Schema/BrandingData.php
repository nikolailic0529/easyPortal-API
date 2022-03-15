<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class BrandingData extends Type {
    public ?string $mainColor;
    public ?string $secondaryColor;
    public ?string $defaultMainColor;
    public ?string $secondaryColorDefault;
    public ?string $defaultLogoUrl;
    public ?string $favIconUrl;
    public ?string $useDefaultFavIcon;
    public ?string $resellerAnalyticsCode;
    public ?string $brandingMode;
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
