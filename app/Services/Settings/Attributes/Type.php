<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use App\Services\Settings\Types\Type as TypeInstance;
use Attribute;
use InvalidArgumentException;

use function is_a;
use function sprintf;

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
        if (!is_a($this->type, TypeInstance::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                TypeInstance::class,
            ));
        }
    }

    public function getType(): TypeInstance {
        return new $this->type();
    }
}
