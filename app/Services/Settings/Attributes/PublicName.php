<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that settings should be available for client.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class PublicName {
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }
}
