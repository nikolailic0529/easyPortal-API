<?php declare(strict_types = 1);

namespace App\Models;

/**
 * @protected string $object_type
 * @protected string $object_id
 */
abstract class PolymorphicModel extends Model {
    // required for type-hints
}
