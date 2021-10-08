<?php declare(strict_types = 1);

/**
 * Mixins for Laravel's classes.
 */

namespace App;

use App\Mixins\TranslatorMixin;
use Illuminate\Translation\Translator;

use function class_exists;

if (class_exists(Translator::class)) {
    Translator::mixin(new TranslatorMixin());
}
