<?php declare(strict_types = 1);

namespace App\Services\I18n;

use LastDragon_ru\LaraASP\Formatter\Formatter as LaraASPFormatter;

/**
 * The class doesn't reflect changes of locale and timezone after creation. It
 * is fine for our application. If you need to track locale/timezone changes
 * you can use {@see CurrentFormatter} instead.
 *
 * @see Provider
 */
class Formatter extends LaraASPFormatter {
    // empty
}
