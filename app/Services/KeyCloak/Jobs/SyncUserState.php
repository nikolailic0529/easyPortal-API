<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Utils\JsonObject;

class SyncUserState extends JsonObject {
    public ?string $continue  = null;
    public ?int    $total     = null;
    public bool    $update    = false;
    public int     $processed = 0;
}
