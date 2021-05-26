<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

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
    public ?string $mainHeadingText;
    public ?string $underlineText;
    public ?string $logoUrl;
}
