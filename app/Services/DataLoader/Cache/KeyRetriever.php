<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface KeyRetriever {
    /**
     * @param TModel $model
     */
    public function getKey(Model $model): Key;
}
