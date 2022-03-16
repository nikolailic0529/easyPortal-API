<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use App\Services\Queue\CronJob;
use Attribute;

/**
 * Marks that setting related to {@link \App\Services\Queue\CronJob}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Service extends Setting {
    /**
     * @param class-string<CronJob> $class
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
