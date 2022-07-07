<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\JsonStorage;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\I18n\Storages\AppTranslations;
use App\Services\I18n\Storages\ClientTranslations;
use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Contracts\Events\Dispatcher;

use function is_array;
use function is_string;
use function ksort;
use function mb_strlen;
use function mb_substr;
use function str_starts_with;

class I18n {
    protected const CLIENT_TRANSLATIONS_PREFIX = 'client.';

    public function __construct(
        protected Dispatcher $dispatcher,
        protected TranslationLoader $loader,
        protected TranslationDefaults $defaults,
        protected AppDisk $appDisk,
        protected ClientDisk $clientDisk,
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
        $data         = $this->getClientStorage($locale)->load();
        $translations = [];

        foreach ($data as $key => $value) {
            // Versions up to v7.6.0 (inclusive) used key value pairs
            if (is_array($value)) {
                $key   = $value['key'] ?? null;
                $value = $value['value'] ?? null;
            }

            // Valid?
            if (is_string($key) && is_string($value)) {
                $translations["{$prefix}{$key}"] = $value;
            }
        }

        ksort($translations);

        return $translations;
    }

    /**
     * @param array<string, ?string> $strings
     */
    public function setTranslations(string $locale, array $strings): bool {
        // Split be type
        $appStrings         = [];
        $clientStrings      = [];
        $clientPrefix       = self::CLIENT_TRANSLATIONS_PREFIX;
        $clientPrefixLength = mb_strlen($clientPrefix);

        foreach ($strings as $key => $value) {
            if (str_starts_with($key, $clientPrefix)) {
                $key                 = mb_substr($key, $clientPrefixLength);
                $clientStrings[$key] = $value;
            } else {
                $appStrings[$key] = $value;
            }
        }

        // Save
        $appResult    = !$appStrings
            || $this->saveTranslations($this->getAppStorage($locale), $appStrings);
        $clientResult = !$clientStrings
            || $this->saveTranslations($this->getClientStorage($locale), $clientStrings);
        $result       = $appResult && $clientResult;

        // Dispatch
        if ($result && ((bool) $appStrings || (bool) $clientStrings)) {
            $this->dispatcher->dispatch(new TranslationsUpdated());
        }

        // Return
        return $result;
    }

    /**
     * @param array<string, ?string> $strings
     */
    protected function saveTranslations(JsonStorage $storage, array $strings): bool {
        $translations = $storage->load();

        foreach ($strings as $key => $string) {
            if ($string !== null) {
                $translations[$key] = $string;
            } else {
                unset($translations[$key]);
            }
        }

        ksort($translations);

        return $storage->save($translations);
    }

    public function resetTranslations(string $locale): bool {
        $this->getAppStorage($locale)->delete(true);
        $this->getClientStorage($locale)->delete(true);

        $this->dispatcher->dispatch(new TranslationsUpdated());

        return true;
    }

    protected function getAppStorage(string $locale): AppTranslations {
        return new AppTranslations($this->appDisk, $locale);
    }

    protected function getClientStorage(string $locale): ClientTranslations {
        return new ClientTranslations($this->clientDisk, $locale);
    }
}
