<?php declare(strict_types = 1);

namespace App\Services;

use App\Services\Filesystem\Disks\AppDisk;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

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

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            $this->app->make(AppDisk::class),
            Mockery::mock(LoggerInterface::class),
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

        $this->assertEquals(
            [
                "{$locale}.loadJsonPaths" => 123,
                "{$locale}.loadStorage"   => 123,
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

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            $this->app->make(AppDisk::class),
            Mockery::mock(LoggerInterface::class),
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
            ->shouldReceive('loadJsonPaths')
            ->with($fallback)
            ->once()
            ->andReturn([
                "{$fallback}.loadJsonPaths" => 123,
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
            ->with($fallback)
            ->once()
            ->andReturn([
                "{$fallback}.loadStorage" => 123,
            ]);

        $this->assertEquals(
            [
                "{$locale}.loadJsonPaths"   => 123,
                "{$fallback}.loadJsonPaths" => 123,
                "{$locale}.loadStorage"     => 123,
                "{$fallback}.loadStorage"   => 123,
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

        $loader = Mockery::mock(TranslationLoader::class, [
            $this->app,
            Mockery::mock(AppDisk::class),
            Mockery::mock(LoggerInterface::class),
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
}
