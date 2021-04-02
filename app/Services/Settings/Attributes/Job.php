<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting related to {@link \LastDragon_ru\LaraASP\Queue\Queueables\Job}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Job extends Name {
    /**
     * @param class-string<\LastDragon_ru\LaraASP\Queue\Contracts\Cronable> $class
     */
    public function __construct(
        protected string $class,
        protected string $name,
    ) {
        parent::__construct("queue.queueables.{$class}.{$name}");
    }
}
