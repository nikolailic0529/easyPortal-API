<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting value is a secret and should not be shown.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Secret {
    public function __construct() {
        // empty
    }
}
