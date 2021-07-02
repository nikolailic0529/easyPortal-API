<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Logger;
use Illuminate\Database\Connection;

use function in_array;

trait Database {
    protected function isConnectionIgnored(Connection $connection): bool {
        return in_array($connection->getName(), [Logger::CONNECTION], true);
    }
}
