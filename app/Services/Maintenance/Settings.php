<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Utils\JsonObject;
use DateTimeInterface;

class Settings extends JsonObject {
    public bool               $enabled  = false;
    public ?string            $message  = null;
    public ?DateTimeInterface $start    = null;
    public ?DateTimeInterface $end      = null;
    public bool               $notified = false;
}
