<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * The group.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Group {
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }
}
