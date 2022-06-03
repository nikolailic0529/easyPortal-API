<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\I18n\Translation\TranslationLoader;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\ProviderDeferred
 */
class ProviderDeferredTest extends TestCase {
    /**
     * @covers ::registerLoader
     */
    public function testRegisterLoader(): void {
        self::assertInstanceOf(
            TranslationLoader::class,
            $this->app->make('translator')->getLoader(),
        );
    }
}
