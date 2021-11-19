<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Utils\Eloquent\Model;

interface KeyRetriever {
    public function get(Model $model): Key;
}
