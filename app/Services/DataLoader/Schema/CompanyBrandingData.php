<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

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
     * @var array<\App\Services\DataLoader\Schema\InputTranslationText>|null
     */
    public ?array $mainHeadingText;

    /**
     * @var array<\App\Services\DataLoader\Schema\InputTranslationText>|null
     */
    public ?array $underlineText;
}
