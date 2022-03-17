<?php declare(strict_types = 1);

namespace Tests\Mixins;

use Closure;
use Illuminate\Translation\Translator;

class TranslatorMixin {
    public function replaceLines(): Closure {
        /**
         * Replace translation lines to the given locale.
         *
         * @param array<string,array<string,string>> $lines
         */
        return function (array $lines, $locale): void {
            foreach ($lines as $key => $value) {
                /**
                 * @var Translator $this
                 * @phpstan-ignore-next-line
                 */
                $this->loaded['*']['*'][$locale][$key] = $value;
            }
        };
    }
}
