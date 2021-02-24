<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;

interface KeyRetriever {
    public function get(Model $model): string|int;
}
