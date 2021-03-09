<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;

class ModelKey implements KeyRetriever {
    public function get(Model $model): string|int {
        return $model->getKey();
    }
}
