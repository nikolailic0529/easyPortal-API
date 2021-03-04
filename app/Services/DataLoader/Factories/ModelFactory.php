<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Model;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Schema\Type;

abstract class ModelFactory extends Factory {
    abstract public function create(Type $type): ?Model;
}
