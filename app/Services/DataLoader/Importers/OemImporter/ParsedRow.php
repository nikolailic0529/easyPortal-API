<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers\OemImporter;

use App\Utils\JsonObject\JsonObject;

class ParsedRow extends JsonObject {
    public Oem $oem;
    public ServiceGroup $serviceGroup;
    public ServiceLevel $serviceLevel;
}
