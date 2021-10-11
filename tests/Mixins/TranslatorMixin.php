<?php declare(strict_types = 1);

namespace Tests\Mixins;

use Closure;

class TranslatorMixin {
    public function replaceLines(): Closure {
        /**
         * Replace translation lines to the given locale.
         *
         * @param array<string,array<string,string>> $lines
         */
        return function (array $lines, $locale): void {
            foreach ($lines as $key => $value) {
                /** @var \Illuminate\Translation\Translator $this */
                $this->loaded['*']['*'][$locale][$key] = $value;
            }
        };
    }
}
