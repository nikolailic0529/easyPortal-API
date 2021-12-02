<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;

interface KeyRetriever {
    public function getKey(Model $model): Key;
}
