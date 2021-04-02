<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting readonly.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Readonly {
    public function __construct() {
        // empty
    }
}
