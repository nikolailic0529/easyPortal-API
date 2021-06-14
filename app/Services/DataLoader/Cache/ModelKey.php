<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;

class ModelKey implements KeyRetriever {
    public function get(Model $model): string|int {
        return $model->getKey();
    }
}
