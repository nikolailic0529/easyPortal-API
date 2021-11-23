<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\JsonObject;

class State extends JsonObject {
    public string|int|null $offset    = null;
    public int             $index     = 0;
    public ?int            $limit     = null;
    public ?int            $total     = null;
    public int             $chunk     = 0;
    public int             $processed = 0;
    public int             $success   = 0;
    public int             $failed    = 0;
    public int             $skipped   = 0;
}
