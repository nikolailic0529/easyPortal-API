<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use Illuminate\Translation\Translator;

/**
 * @mixin \Tests\TestCase
 */
trait WithTranslations {
    /**
     * @param \Closure(\Tests\TestCase $test,string $locale, string $fallback):array<string,array<string,string>>|null
     *      $translations
     */
    public function setTranslations(Closure|null $translations): void {
        /**
         * FIXME [test] This is doesn't work properly for HTTP requests when
         * Locale set by {@see \App\Services\LocaleService}.
         */
        $translator   = $this->app->make(Translator::class);
        $translations = $translations instanceof Closure
            ? $translations($this, $translator->getLocale(), $translator->getFallback())
            : [];

        foreach ((array) $translations as $locale => $lines) {
            $translator->load('*', '*', $locale);
            $translator->replaceLines($lines, $locale);
        }
    }
}
