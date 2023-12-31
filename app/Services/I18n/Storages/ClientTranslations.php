<?php declare(strict_types = 1);

namespace App\Services\I18n\Storages;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\JsonStorage;

class ClientTranslations extends JsonStorage {
    public function __construct(ClientDisk $disc, string $locale) {
        parent::__construct($disc, "lang/{$locale}.json");
    }
}
