<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Storages;

use App\Services\Filesystem\Disks\UIDisk;
use App\Services\Filesystem\JsonStorage;

class UITranslations extends JsonStorage {
    public function __construct(UIDisk $disc, string $locale) {
        parent::__construct($disc, "lang/{$locale}.json");
    }
}
