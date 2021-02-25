<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Schema\Type;

/**
 * Factories implement logic on how to create an application's model from an
 * external entity.
 */
interface Factory {
    public function create(Type $type): Model;
}
