<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting related to {@link \LastDragon_ru\LaraASP\Queue\Queueables\CronJob}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Service extends Setting {
    /**
     * @param class-string<\LastDragon_ru\LaraASP\Queue\Queueables\Job> $class
     */
    public function __construct(
        protected string $class,
        protected string $name,
    ) {
        parent::__construct("queue.queueables.{$class}.{$name}");
    }

    public function getClass(): string {
        return $this->class;
    }
}
