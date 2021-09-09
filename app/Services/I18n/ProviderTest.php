<?php declare(strict_types = 1);

namespace App\Services\I18n;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Provider
 */
class ProviderTest extends TestCase {
    /**
     * @covers ::registerLoader
     */
    public function testRegisterLoader(): void {
        $this->assertInstanceOf(
            TranslationLoader::class,
            $this->app->make('translator')->getLoader(),
        );
    }
}
