<?php declare(strict_types = 1);

namespace App\Services\I18n;

use DateTimeZone;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use IntlTimeZone;
use LastDragon_ru\LaraASP\Formatter\Formatter as LaraASPFormatter;
use LastDragon_ru\LaraASP\Formatter\PackageTranslator;

class Formatter extends LaraASPFormatter {
    public function __construct(
        Application $application,
        Repository $config,
        PackageTranslator $translator,
        protected CurrentLocale $locale,
        protected CurrentTimezone $timezone,
    ) {
        parent::__construct($application, $config, $translator);
    }

    protected function getDefaultLocale(): string {
        return $this->locale->get();
    }

    protected function getDefaultTimezone(): IntlTimeZone|DateTimeZone|string|null {
        return $this->timezone->get();
    }
}
