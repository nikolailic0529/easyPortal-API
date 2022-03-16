<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use Illuminate\Translation\Translator;

/**
 * @mixin TestCase
 */
trait WithTranslations {
    /**
     * @param Closure(TestCase, string, string):array<string,array<string,string>>
     *     |array<string,array<string,string>>
     *     |null $translations
     */
    public function setTranslations(Closure|array|null $translations): void {
        /**
         * FIXME [test] This is doesn't work properly for HTTP requests when
         * Locale set by {@see \App\Services\I18n\Locale}.
         */
        $translator = $this->app->make(Translator::class);

        if ($translations instanceof Closure) {
            $translations = $translations($this, $translator->getLocale(), $translator->getFallback());
        }

        foreach ((array) $translations as $locale => $lines) {
            $translator->load('*', '*', $locale);
            $translator->replaceLines($lines, $locale);
        }
    }
}
