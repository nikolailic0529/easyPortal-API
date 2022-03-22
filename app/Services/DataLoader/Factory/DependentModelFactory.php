<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class DependentModelFactory extends Factory {
    /**
     * @return TModel|null
     */
    abstract public function create(Model $object, Type $type): ?Model;

    /**
     * @return TModel|null
     */
    public function find(Model $object, Type $type): ?Model {
        return $this->inSearchMode(function () use ($object, $type): ?Model {
            return $this->create($object, $type);
        });
    }
}
