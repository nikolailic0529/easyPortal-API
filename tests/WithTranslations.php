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
        $translator   = $this->app->make(Translator::class);
        $translations = $translations instanceof Closure
            ? $translations($this, $translator->getLocale(), $translator->getFallback())
            : [];

        foreach ((array) $translations as $locale => $lines) {
            $translator->replaceLines($lines, $locale);
        }
    }
}
