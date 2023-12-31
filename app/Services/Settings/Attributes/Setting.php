<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * The setting.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Setting {
    public function __construct(
        protected ?string $path = null,
    ) {
        // empty
    }

    public function getPath(): ?string {
        return $this->path;
    }
}
