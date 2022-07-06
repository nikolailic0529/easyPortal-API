<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use LastDragon_ru\LaraASP\Core\Utils\Cast;

use function ksort;

class I18n {
    protected const CLIENT_TRANSLATIONS_PREFIX = 'client.';

    public function __construct(
        protected TranslationLoader $loader,
        protected TranslationDefaults $defaults,
        protected ClientDisk $disk,
    ) {
        // empty
    }

    /**
     * @return array<string, string>
     */
    public function getTranslations(string $locale): array {
        return []
            + $this->getAppTranslations($locale)
            + $this->getDefaultTranslations($locale)
            + $this->getClientTranslations($locale, self::CLIENT_TRANSLATIONS_PREFIX);
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultTranslations(string $locale): array {
        $translations = $this->defaults->getTranslations($locale);

        ksort($translations);

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    protected function getAppTranslations(string $locale): array {
        $translations = $this->loader->getTranslations($locale);

        ksort($translations);

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    public function getClientTranslations(string $locale, string $prefix = ''): array {
        $data         = (new ClientTranslations($this->disk, $locale))->load();
        $translations = [];

        foreach ($data as $key => $value) {
            $translations["{$prefix}{$key}"] = Cast::toString($value);
        }

        ksort($translations);

        return $translations;
    }
}
