<?php declare(strict_types = 1);

namespace App\Mixins;

use Closure;

class TranslatorMixin {
    public function replaceLines(): Closure {
        /**
         * Replace translation lines to the given locale.
         *
         * @param array<string,array<string,string>> $lines
         */
        return function (array $lines, $locale): void {
            /** @var \Illuminate\Translation\Translator $this */
            foreach ($lines as $key => $value) {
                $this->loaded['*']['*'][$locale][$key] = $value;
            }
        };
    }
}
