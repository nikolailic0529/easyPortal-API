<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Utils\JsonObject;

class QueueJob extends JsonObject {
    public const STATUS_FAILED    = 'failed';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_COMPLETED = 'completed';

    public string  $id;
    public string  $name;
    public string  $payload;
    public string  $connection;
    public string  $queue;
    public string  $status;
    public int     $index;
    public ?string $updated_at;
    public ?string $reserved_at;
    public ?string $completed_at;
}
