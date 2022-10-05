<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\OemsImporter;

use App\Utils\JsonObject\JsonObject;

class ServiceLevel extends JsonObject {
    public string  $sku;
    public ?string $name        = null;
    public ?string $description = null;
    /**
     * @var array<string,array<string,string>>
     */
    public array $translations;
}
