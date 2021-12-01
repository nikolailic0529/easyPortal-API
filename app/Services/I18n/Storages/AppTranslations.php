<?php declare(strict_types = 1);

namespace App\Services\I18n\Storages;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\JsonStorage;

class AppTranslations extends JsonStorage {
    public function __construct(AppDisk $disc, string $locale) {
        parent::__construct($disc, "lang/{$locale}.json");
    }
}
