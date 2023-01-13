<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\Filesystem\Disks\AppDisk;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Tests\TestCase;

use function explode;

/**
 * @internal
 * @covers \App\Services\I18n\Translation\TranslationLoader
 */
class TranslationLoaderTest extends TestCase {
    public function testLoadFallbackSame(): void {
        $locale   = 'en';
        $fallback = 'en';

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            $this->app->make(AppDisk::class),
            Mockery::mock(ExceptionHandler::class),
            $this->app->make(Filesystem::class),
            '',
        ]);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();

        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($locale)
            ->once()
            ->andReturn([
                "{$locale}.loadJsonPaths" => 123,
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->with($locale)
            ->once()
            ->andReturn([
                "{$locale}.loadStorage" => 123,
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->never();

        self::assertEquals(
            [
                "{$locale}.loadJsonPaths" => 123,
                "{$locale}.loadStorage"   => 123,
            ],
            $loader->load($locale, '*', '*'),
        );
    }

    public function testLoadBaseAndFallback(): void {
        $locale       = 'de_DE';
        $localeBase   = explode('_', $locale)[0];
        $fallback     = 'en_GB';
        $fallbackBase = explode('_', $fallback)[0];

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        self::assertNotEquals($locale, $fallback);

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            $this->app->make(AppDisk::class),
            Mockery::mock(ExceptionHandler::class),
            $this->app->make(Filesystem::class),
            '',
        ]);
        $loader->shouldAllowMockingProtectedMethods();
        $loader->makePartial();

        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($locale)
            ->once()
            ->andReturn([
                "{$locale}.loadJsonPaths" => 123,
                'string'                  => "{$locale}.loadJsonPaths",
            ]);
        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($localeBase)
            ->once()
            ->andReturn([
                "{$localeBase}.loadJsonPaths" => 123,
                'string'                      => "{$localeBase}.loadJsonPaths",
            ]);
        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($fallback)
            ->once()
            ->andReturn([
                "{$fallback}.loadJsonPaths" => 123,
                'string'                    => "{$fallback}.loadJsonPaths",
            ]);
        $loader
            ->shouldReceive('loadJsonPaths')
            ->with($fallbackBase)
            ->once()
            ->andReturn([
                "{$fallbackBase}.loadJsonPaths" => 123,
                'string'                        => "{$fallbackBase}.loadJsonPaths",
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->with($locale)
            ->once()
            ->andReturn([
                "{$locale}.loadStorage" => 123,
                'string'                => "{$locale}.loadStorage",
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->with($localeBase)
            ->once()
            ->andReturn([
                "{$localeBase}.loadStorage" => 123,
                'string'                    => "{$localeBase}.loadStorage",
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->with($fallback)
            ->once()
            ->andReturn([
                "{$fallback}.loadStorage" => 123,
                'string'                  => "{$fallback}.loadStorage",
            ]);
        $loader
            ->shouldReceive('loadStorage')
            ->with($fallbackBase)
            ->once()
            ->andReturn([
                "{$fallbackBase}.loadStorage" => 123,
                'string'                      => "{$fallback}.loadStorage",
            ]);

        self::assertEquals(
            [
                'string'                        => "{$locale}.loadStorage",
                "{$locale}.loadJsonPaths"       => 123,
                "{$localeBase}.loadJsonPaths"   => 123,
                "{$fallback}.loadJsonPaths"     => 123,
                "{$fallbackBase}.loadJsonPaths" => 123,
                "{$locale}.loadStorage"         => 123,
                "{$localeBase}.loadStorage"     => 123,
                "{$fallback}.loadStorage"       => 123,
                "{$fallbackBase}.loadStorage"   => 123,
            ],
            $loader->load($locale, '*', '*'),
        );
    }

    public function testLoadFallbackNotCalled(): void {
        $locale   = 'ru';
        $fallback = 'en';

        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($fallback);

        self::assertNotEquals($locale, $fallback);

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            Mockery::mock(AppDisk::class),
            Mockery::mock(ExceptionHandler::class),
            $this->app->make(Filesystem::class),
            '',
        ]);
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

    public function testGetTranslations(): void {
        $locale = $this->faker->locale();
        $loader = Mockery::mock(TranslationLoader::class);
        $loader->makePartial();

        $loader
            ->shouldReceive('load')
            ->with($locale, '*', '*')
            ->once()
            ->andReturn([]);

        $loader->getTranslations($locale);
    }
}
