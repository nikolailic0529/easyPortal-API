<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting internal (internal settings cannot be edited by UI).
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Internal {
    public function __construct() {
        // empty
    }
}
