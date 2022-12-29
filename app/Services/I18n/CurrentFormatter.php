<?php declare(strict_types = 1);

namespace App\Services\I18n;

use DateTimeZone;
use IntlTimeZone;
use LastDragon_ru\LaraASP\Formatter\PackageTranslator;

class CurrentFormatter extends Formatter {
    public function __construct(
        PackageTranslator $translator,
        protected CurrentLocale $locale,
        protected CurrentTimezone $timezone,
    ) {
        parent::__construct($translator);
    }

    protected function getDefaultLocale(): string {
        return $this->locale->get();
    }

    protected function getDefaultTimezone(): IntlTimeZone|DateTimeZone|string|null {
        return $this->timezone->get();
    }
}
