<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting can be null.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Nullable {
    public function __construct() {
        // empty
    }
}
