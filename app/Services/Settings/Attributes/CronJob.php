<?php declare(strict_types = 1);

namespace App\Services\Settings\Attributes;

use Attribute;

/**
 * Marks that setting related to {@link \LastDragon_ru\LaraASP\Queue\Queueables\CronJob}.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class CronJob extends Job {
    // empty
}
