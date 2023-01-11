<?php declare(strict_types = 1);

namespace App\Services\I18n;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\JsonStorage;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\I18n\Storages\AppTranslations;
use App\Services\I18n\Storages\ClientTranslations;
use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function array_keys;
use function array_values;

/**
 * @internal
 * @covers \App\Services\I18n\I18n
 */
class I18nTest extends TestCase {
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

    public function testGetClientTranslations(): void {
        $locale  = $this->faker->locale();
        $disk    = $this->app->make(ClientDisk::class);
        $storage = new ClientTranslations($disk, $locale);

        $storage->save([
            'c' => 'c',
            ['key' => 'b', 'value' => 'b'],
            ['key' => 'a', 'value' => 'a'],
        ]);

        // No prefix
        $i18n         = $this->app->make(I18n::class);
        $translations = $i18n->getClientTranslations($locale);
        $expected     = [
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
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
            'prefix-c' => 'c',
        ];

        self::assertEquals($expected, $translations);
        self::assertEquals(array_keys($expected), array_keys($translations));
        self::assertEquals(array_values($expected), array_values($translations));
    }

    public function testSaveTranslations(): void {
        $i18n = new class() extends I18n {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function saveTranslations(JsonStorage $storage, array $strings): bool {
                return parent::saveTranslations($storage, $strings);
            }
        };

        $storage = Mockery::mock(JsonStorage::class);
        $storage
            ->shouldReceive('load')
            ->once()
            ->andReturn([
                'b' => 'should be unchanged',
                'a' => 'should be updated',
                'c' => 'should be removed',
            ]);
        $storage
            ->shouldReceive('save')
            ->with([
                'a' => 'updated value',
                'b' => 'should be unchanged',
            ])
            ->once()
            ->andReturn(true);

        self::assertTrue($i18n->saveTranslations($storage, [
            'a' => 'updated value',
            'c' => null,
        ]));
    }

    public function testSetTranslations(): void {
        Event::fake(TranslationsUpdated::class);

        $locale        = $this->faker->locale();
        $appStorage    = Mockery::mock(AppTranslations::class);
        $clientStorage = Mockery::mock(ClientTranslations::class);
        $i18n          = Mockery::mock(I18n::class, [
            $this->app->make(Dispatcher::class),
            Mockery::mock(TranslationLoader::class),
            Mockery::mock(TranslationDefaults::class),
            Mockery::mock(AppDisk::class),
            Mockery::mock(ClientDisk::class),
        ]);
        $i18n->shouldAllowMockingProtectedMethods();
        $i18n->makePartial();
        $i18n
            ->shouldReceive('getAppStorage')
            ->once()
            ->andReturn($appStorage);
        $i18n
            ->shouldReceive('getClientStorage')
            ->once()
            ->andReturn($clientStorage);
        $i18n
            ->shouldReceive('saveTranslations')
            ->with($appStorage, [
                'a' => 'app',
            ])
            ->once()
            ->andReturn(true);
        $i18n
            ->shouldReceive('saveTranslations')
            ->with($clientStorage, [
                'a' => 'client',
            ])
            ->once()
            ->andReturn(true);

        self::assertTrue($i18n->setTranslations($locale, [
            'a'        => 'app',
            'client.a' => 'client',
        ]));

        Event::assertDispatched(TranslationsUpdated::class);
    }

    public function testSetTranslationsEmpty(): void {
        $locale = $this->faker->locale();
        $i18n   = Mockery::mock(I18n::class);
        $i18n->shouldAllowMockingProtectedMethods();
        $i18n->makePartial();
        $i18n
            ->shouldReceive('saveTranslations')
            ->never();
        $i18n
            ->shouldReceive('saveTranslations')
            ->never();

        self::assertTrue($i18n->setTranslations($locale, [
            // empty
        ]));
    }

    public function testSetTranslationsAppOnly(): void {
        Event::fake(TranslationsUpdated::class);

        $locale     = $this->faker->locale();
        $appStorage = Mockery::mock(AppTranslations::class);
        $i18n       = Mockery::mock(I18n::class, [
            $this->app->make(Dispatcher::class),
            Mockery::mock(TranslationLoader::class),
            Mockery::mock(TranslationDefaults::class),
            Mockery::mock(AppDisk::class),
            Mockery::mock(ClientDisk::class),
        ]);
        $i18n->shouldAllowMockingProtectedMethods();
        $i18n->makePartial();
        $i18n
            ->shouldReceive('getAppStorage')
            ->once()
            ->andReturn($appStorage);
        $i18n
            ->shouldReceive('saveTranslations')
            ->with($appStorage, [
                'a' => 'app',
            ])
            ->once()
            ->andReturn(true);
        $i18n
            ->shouldReceive('saveTranslations')
            ->never();

        self::assertTrue($i18n->setTranslations($locale, [
            'a' => 'app',
        ]));

        Event::assertDispatched(TranslationsUpdated::class);
    }

    public function testSetTranslationsClientOnly(): void {
        Event::fake(TranslationsUpdated::class);

        $locale        = $this->faker->locale();
        $clientStorage = Mockery::mock(ClientTranslations::class);
        $i18n          = Mockery::mock(I18n::class, [
            $this->app->make(Dispatcher::class),
            Mockery::mock(TranslationLoader::class),
            Mockery::mock(TranslationDefaults::class),
            Mockery::mock(AppDisk::class),
            Mockery::mock(ClientDisk::class),
        ]);
        $i18n->shouldAllowMockingProtectedMethods();
        $i18n->makePartial();
        $i18n
            ->shouldReceive('getClientStorage')
            ->once()
            ->andReturn($clientStorage);
        $i18n
            ->shouldReceive('saveTranslations')
            ->never();
        $i18n
            ->shouldReceive('saveTranslations')
            ->with($clientStorage, [
                'a' => 'client',
            ])
            ->once()
            ->andReturn(true);

        self::assertTrue($i18n->setTranslations($locale, [
            'client.a' => 'client',
        ]));

        Event::assertDispatched(TranslationsUpdated::class);
    }

    public function testResetTranslations(): void {
        Event::fake(TranslationsUpdated::class);

        $locale     = $this->faker->locale();
        $appStorage = Mockery::mock(AppTranslations::class);
        $appStorage
            ->shouldReceive('delete')
            ->with(true)
            ->once()
            ->andReturn(true);

        $clientStorage = Mockery::mock(ClientTranslations::class);
        $clientStorage
            ->shouldReceive('delete')
            ->with(true)
            ->once()
            ->andReturn(true);

        $i18n = Mockery::mock(I18n::class, [
            $this->app->make(Dispatcher::class),
            Mockery::mock(TranslationLoader::class),
            Mockery::mock(TranslationDefaults::class),
            Mockery::mock(AppDisk::class),
            Mockery::mock(ClientDisk::class),
        ]);
        $i18n->shouldAllowMockingProtectedMethods();
        $i18n->makePartial();
        $i18n
            ->shouldReceive('getAppStorage')
            ->once()
            ->andReturn($appStorage);
        $i18n
            ->shouldReceive('getClientStorage')
            ->once()
            ->andReturn($clientStorage);

        self::assertTrue($i18n->resetTranslations($locale));

        Event::assertDispatched(TranslationsUpdated::class);
    }
}
