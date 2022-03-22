<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class CompanyBrandingData extends Input {
    public string  $id;
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

    /**
     * @var array<InputTranslationText>|null
     */
    #[JsonObjectArray(InputTranslationText::class)]
    public ?array $mainHeadingText;

    /**
     * @var array<InputTranslationText>|null
     */
    #[JsonObjectArray(InputTranslationText::class)]
    public ?array $underlineText;
}
