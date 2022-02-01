<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Utils\JsonObject\JsonObject;

class UpdateIndexState extends JsonObject {
    public ?string $from      = null;
    public ?string $continue  = null;
    public ?int    $total     = null;
    public bool    $update    = false;
    public int     $processed = 0;
}
