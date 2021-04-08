<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting related to {@link \LastDragon_ru\LaraASP\Queue\Queueables\CronJob}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Service extends Job {
    /**
     * @param class-string<\LastDragon_ru\LaraASP\Queue\Queueables\CronJob> $class
     */
    public function __construct(string $class, string $name) {
        parent::__construct($class, $name);
    }
}
