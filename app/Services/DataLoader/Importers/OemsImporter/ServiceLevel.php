<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers\OemsImporter;

use App\Utils\JsonObject\JsonObject;

class ServiceLevel extends JsonObject {
    public string $sku;
    public string $name;
    public string $description;
    /**
     * @var array<string,array<string,string>>
     */
    public array $translations;
}
