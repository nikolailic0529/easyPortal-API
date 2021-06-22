<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Utils\JsonObject;

class ImporterState extends JsonObject {
    public ?string $from      = null;
    public ?string $continue  = null;
    public int     $processed = 0;
}
