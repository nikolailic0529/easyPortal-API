<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Services\DataLoader\Normalizer;
use Illuminate\Database\Eloquent\Model;

class ModelKey implements KeyRetriever {
    public function __construct(
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    public function get(Model $model): Key {
        return new Key($this->normalizer, [$model->getKey()]);
    }
}
