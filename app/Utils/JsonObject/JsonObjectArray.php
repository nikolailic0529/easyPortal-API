<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use Attribute;

/**
 * Used to specify the type of array items.
 *
 * Not sure that it is a best way, but alternative of this is parse docblock and
 * search for `use` statements in file (that slow and difficult).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonObjectArray {
    /**
     * @param class-string $type
     */
    public function __construct(
        protected string $type,
    ) {
        // empty
    }

    /**
     * @return class-string
     */
    public function getType(): string {
        return $this->type;
    }
}
