<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\OemsImporter;

use App\Utils\JsonObject\JsonObject;

class ParsedRow extends JsonObject {
    public Oem $oem;
    public ServiceGroup $serviceGroup;
    public ServiceLevel $serviceLevel;
}
