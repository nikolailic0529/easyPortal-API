<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Logger\Models\Log;
use Illuminate\Database\Eloquent\Builder;

class Logs {
    public function __construct() {
        // empty
    }

    public function __invoke(): Builder {
        return Log::query()->whereNull('parent_id');
    }
}
