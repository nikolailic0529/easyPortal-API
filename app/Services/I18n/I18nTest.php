<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\I18n\Storages\ClientTranslations;
use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Mockery\MockInterface;
use Tests\TestCase;

use function array_keys;
use function array_values;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\I18n
 */
class I18nTest extends TestCase {
    /**
     * @covers ::getTranslations
     */
    public function testGetTranslations(): void {
        $locale = $this->faker->locale();

        $this->override(TranslationLoader::class, static function (MockInterface $mock) use ($locale): void {
            $mock
                ->shouldReceive('getTranslations')
                ->with($locale)
                ->once()
                ->andReturn([
                    'app-b' => 'app-b',
                    'app-a' => 'app-a',
                ]);
        });
        $this->override(TranslationDefaults::class, static function (MockInterface $mock) use ($locale): void {
            $mock
                ->shouldReceive('getTranslations')
                ->with($locale)
                ->once()
                ->andReturn([
                    'app-defaults-b' => 'app-defaults-b',
                    'app-defaults-a' => 'app-defaults-a',
                ]);
        });

        $disk    = $this->app->make(ClientDisk::class);
        $storage = new ClientTranslations($disk, $locale);

        $storage->save([
            'b' => 'b',
            'a' => 'a',
        ]);

        $i18n         = $this->app->make(I18n::class);
        $translations = $i18n->getTranslations($locale);
        $expected     = [
            'app-a'          => 'app-a',
            'app-b'          => 'app-b',
            'app-defaults-a' => 'app-defaults-a',
            'app-defaults-b' => 'app-defaults-b',
            'client.a'       => 'a',
            'client.b'       => 'b',
        ];

        self::assertEquals($expected, $translations);
        self::assertEquals(array_keys($expected), array_keys($translations));
        self::assertEquals(array_values($expected), array_values($translations));
    }

    /**
     * @covers ::getDefaultTranslations
     */
    public function testGetDefaultTranslations(): void {
        $locale = $this->faker->locale();

        $this->override(TranslationDefaults::class, static function (MockInterface $mock) use ($locale): void {
            $mock
                ->shouldReceive('getTranslations')
                ->with($locale)
                ->once()
                ->andReturn([
                    'b' => 'b',
                    'a' => 'a',
                ]);
        });

        $i18n         = $this->app->make(I18n::class);
        $translations = $i18n->getDefaultTranslations($locale);
        $expected     = [
            'a' => 'a',
            'b' => 'b',
        ];

        self::assertEquals($expected, $translations);
        self::assertEquals(array_keys($expected), array_keys($translations));
        self::assertEquals(array_values($expected), array_values($translations));
    }

    /**
     * @covers ::getClientTranslations
     */
    public function testGetClientTranslations(): void {
        $locale  = $this->faker->locale();
        $disk    = $this->app->make(ClientDisk::class);
        $storage = new ClientTranslations($disk, $locale);

        $storage->save([
            'b' => 'b',
            'a' => 'a',
        ]);

        // No prefix
        $i18n         = $this->app->make(I18n::class);
        $translations = $i18n->getClientTranslations($locale);
        $expected     = [
            'a' => 'a',
            'b' => 'b',
        ];

        self::assertEquals($expected, $translations);
        self::assertEquals(array_keys($expected), array_keys($translations));
        self::assertEquals(array_values($expected), array_values($translations));

        // Prefix
        $i18n         = $this->app->make(I18n::class);
        $translations = $i18n->getClientTranslations($locale, 'prefix-');
        $expected     = [
            'prefix-a' => 'a',
            'prefix-b' => 'b',
        ];

        self::assertEquals($expected, $translations);
        self::assertEquals(array_keys($expected), array_keys($translations));
        self::assertEquals(array_values($expected), array_values($translations));
    }
}
