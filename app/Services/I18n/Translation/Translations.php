<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\I18n\Storages\AppTranslations;
use Illuminate\Contracts\Events\Dispatcher;

class Translations {
    public function __construct(
        protected Dispatcher $dispatcher,
        protected AppDisk $disk,
    ) {
        // empty
    }

    /**
     * @param array<string, string> $strings
     * @param array<string>         $updated
     */
    public function update(string $locale, array $strings, array &$updated = []): bool {
        // Load
        $storage      = $this->getStorage($locale);
        $translations = $storage->load();

        // Update
        foreach ($strings as $key => $string) {
            $translations[$key] = $string;
            $updated[]          = $key;
        }

        // Save
        $result = $storage->save($translations);

        if ($result && $updated) {
            $this->dispatcher->dispatch(new TranslationsUpdated());
        } else {
            $updated = [];
        }

        return $result;
    }

    /**
     * @param array<string> $keys
     * @param array<string> $deleted
     */
    public function delete(string $locale, array $keys, array &$deleted = []): bool {
        // Load
        $storage      = $this->getStorage($locale);
        $translations = $storage->load();

        // Update
        foreach ($keys as $key) {
            if (isset($translations[$key])) {
                $deleted[] = $key;

                unset($translations[$key]);
            }
        }

        // Save
        $result = $storage->save($translations);

        if ($result && $deleted) {
            $this->dispatcher->dispatch(new TranslationsUpdated());
        } else {
            $deleted = [];
        }

        return $result;
    }

    public function reset(string $locale): bool {
        $result = $this->getStorage($locale)->delete(true);

        if ($result) {
            $this->dispatcher->dispatch(new TranslationsUpdated());
        }

        return $result;
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations($this->disk, $locale);
    }
}
