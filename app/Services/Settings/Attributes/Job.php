<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use App\Services\Queue\Job as QueueJob;
use Attribute;

/**
 * Marks that setting related to {@link \App\Services\Queue\Job}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Job extends Setting {
    /**
     * @param class-string<QueueJob> $class
     */
    public function __construct(
        protected string $class,
        string $path,
    ) {
        parent::__construct("queue.queueables.{$class}.{$path}");
    }

    public function getClass(): string {
        return $this->class;
    }
}
