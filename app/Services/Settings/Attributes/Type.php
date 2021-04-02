<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * The setting type.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Type {
    /**
     * @param class-string<\App\Services\Settings\Types\Type> $type
     */
    public function __construct(
        protected string $type,
    ) {
        // empty
    }

    /**
     * @return class-string<\App\Services\Settings\Types\Type>
     */
    public function getType(): string {
        return $this->type;
    }
}
