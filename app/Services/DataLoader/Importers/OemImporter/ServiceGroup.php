<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers\OemImporter;

use App\Utils\JsonObject\JsonObject;

class ServiceGroup extends JsonObject {
    public string $sku;
    public string $name;
}
