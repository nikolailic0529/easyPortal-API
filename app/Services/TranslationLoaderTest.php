<?php declare(strict_types = 1);

namespace App\Services;

use App\GraphQL\Mutations\UpdateApplicationTranslations;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\Services\TranslationLoader
 */
class TranslationLoaderTest extends TestCase {
    /**
     * @covers ::load
     */
    public function testLoadFallbackSame(): void {
        $locale   = 'en';
        $fallback = 'en';

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        $loader = Mockery::mock(TranslationLoader::class, [$this->app, $this->app->make(Filesystem::class), '']);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();

        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($locale)
            ->twice()
            ->andReturn([
                $locale => 123,
            ]);

        $this->assertEquals(
            [
                $locale => 123,
            ],
            $loader->load($locale, '*', '*'),
        );
    }

    /**
     * @covers ::load
     */
    public function testLoadFallbackNotSame(): void {
        $locale   = 'ru';
        $fallback = 'en';

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        $this->assertNotEquals($locale, $fallback);

        $loader = Mockery::mock(TranslationLoader::class, [$this->app, $this->app->make(Filesystem::class), '']);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();

        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($locale)
            ->twice()
            ->andReturn([
                $locale => 123,
            ]);
        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($fallback)
            ->once()
            ->andReturn([
                $fallback => 123,
            ]);

        $this->assertEquals(
            [
                $locale   => 123,
                $fallback => 123,
            ],
            $loader->load($locale, '*', '*'),
        );
    }

    /**
     * @covers ::load
     */
    public function testLoadFallbackNotCalled(): void {
        $locale   = 'ru';
        $fallback = 'en';

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        $this->assertNotEquals($locale, $fallback);

        $loader = Mockery::mock(TranslationLoader::class, [$this->app, $this->app->make(Filesystem::class), '']);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();

        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($fallback)
            ->never();

        $loader->load($locale, 'group');
        $loader->load($locale, 'group', '*');
        $loader->load($locale, 'group', 'namespace');
        $loader->load($locale, '*');
        $loader->load($locale, '*', 'namespace');
    }

    /**
     * @covers ::load
     */
    public function testLoadFromStorage(): void {
        $locale = 'ru';

        $this->app->setLocale($locale);

        $mutation = $this->app->make(UpdateApplicationTranslations::class);
        Storage::fake($mutation->getDisc()->getValue())
            ->put($mutation->getFile($locale), '{"group.test":"translated"}');
        $this->assertEquals('translated', __('group.test'));
    }
}
