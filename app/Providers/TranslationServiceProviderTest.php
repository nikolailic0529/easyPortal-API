<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\I18n\TranslationLoader;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Providers\TranslationServiceProvider
 */
class TranslationServiceProviderTest extends TestCase {
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
