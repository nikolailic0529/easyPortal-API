<?php declare(strict_types = 1);

namespace App\Services\I18n\Translation;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Events\TranslationsUpdated;
use App\Services\I18n\Storages\AppTranslations;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

use function array_merge;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Translation\Translations
 */
class TranslationsTest extends TestCase {
    /**
     * @covers ::update
     */
    public function testUpdate(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            public function getStorage(string $locale): AppTranslations {
                return parent::getStorage($locale);
            }
        };

        $locale  = $this->faker->locale;
        $storage = $translations->getStorage($locale);
        $initial = [
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
        ];

        $storage->save($initial);

        $updated = [];
        $strings = [
            'a' => 'aa',
            'c' => 'cс',
            'd' => 'dd',
        ];

        self::assertTrue($translations->update($locale, $strings, $updated));
        self::assertEquals(['a', 'c', 'd'], $updated);
        self::assertEquals(array_merge($initial, $strings), $storage->load());

        Event::assertDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::update
     */
    public function testUpdateFailed(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            protected function getStorage(string $locale): AppTranslations {
                $mock = Mockery::mock(AppTranslations::class);
                $mock
                    ->shouldReceive('load')
                    ->once()
                    ->andReturn([
                        'a' => 'a',
                        'b' => 'b',
                        'c' => 'c',
                    ]);
                $mock
                    ->shouldReceive('save')
                    ->once()
                    ->andReturn(false);

                return $mock;
            }
        };

        $locale  = $this->faker->locale;
        $updated = [];

        self::assertFalse($translations->update($locale, [], $updated));
        self::assertEquals([], $updated);

        Event::assertNotDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::update
     */
    public function testUpdateNoChanges(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            protected function getStorage(string $locale): AppTranslations {
                $mock = Mockery::mock(AppTranslations::class);
                $mock
                    ->shouldReceive('load')
                    ->once()
                    ->andReturn([]);
                $mock
                    ->shouldReceive('save')
                    ->once()
                    ->andReturn(true);

                return $mock;
            }
        };

        $locale  = $this->faker->locale;
        $updated = [];

        self::assertTrue($translations->update($locale, [], $updated));
        self::assertEquals([], $updated);

        Event::assertNotDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            public function getStorage(string $locale): AppTranslations {
                return parent::getStorage($locale);
            }
        };

        $locale  = $this->faker->locale;
        $storage = $translations->getStorage($locale);

        $storage->save([
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
        ]);

        $deleted = [];

        self::assertTrue($translations->delete($locale, ['a', 'a', 'c', 'd'], $deleted));
        self::assertEquals(['a', 'c'], $deleted);
        self::assertEquals(['b' => 'b'], $storage->load());

        Event::assertDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteFailed(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            protected function getStorage(string $locale): AppTranslations {
                $mock = Mockery::mock(AppTranslations::class);
                $mock
                    ->shouldReceive('load')
                    ->once()
                    ->andReturn([
                        'a' => 'a',
                        'b' => 'b',
                        'c' => 'c',
                    ]);
                $mock
                    ->shouldReceive('save')
                    ->once()
                    ->andReturn(false);

                return $mock;
            }
        };

        $locale  = $this->faker->locale;
        $deleted = [];

        self::assertFalse($translations->delete($locale, [], $deleted));
        self::assertEquals([], $deleted);

        Event::assertNotDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteNoChanges(): void {
        Event::fake(TranslationsUpdated::class);

        $disk         = $this->app->make(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = new class($dispatcher, $disk) extends Translations {
            protected function getStorage(string $locale): AppTranslations {
                $mock = Mockery::mock(AppTranslations::class);
                $mock
                    ->shouldReceive('load')
                    ->once()
                    ->andReturn([]);
                $mock
                    ->shouldReceive('save')
                    ->once()
                    ->andReturn(true);

                return $mock;
            }
        };

        $locale  = $this->faker->locale;
        $deleted = [];

        self::assertTrue($translations->delete($locale, [], $deleted));
        self::assertEquals([], $deleted);

        Event::assertNotDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        Event::fake(TranslationsUpdated::class);

        $locale  = $this->faker->locale;
        $storage = Mockery::mock(AppTranslations::class);
        $storage
            ->shouldReceive('delete')
            ->with(true)
            ->once()
            ->andReturn(true);

        $disk         = Mockery::mock(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = Mockery::mock(Translations::class, [$dispatcher, $disk]);
        $translations->shouldAllowMockingProtectedMethods();
        $translations->makePartial();
        $translations
            ->shouldReceive('getStorage')
            ->with($locale)
            ->once()
            ->andReturn($storage);

        self::assertTrue($translations->reset($locale));

        Event::assertDispatched(TranslationsUpdated::class);
    }

    /**
     * @covers ::reset
     */
    public function testResetFailed(): void {
        Event::fake(TranslationsUpdated::class);

        $locale  = $this->faker->locale;
        $storage = Mockery::mock(AppTranslations::class);
        $storage
            ->shouldReceive('delete')
            ->with(true)
            ->once()
            ->andReturn(false);

        $disk         = Mockery::mock(AppDisk::class);
        $dispatcher   = $this->app->make(Dispatcher::class);
        $translations = Mockery::mock(Translations::class, [$dispatcher, $disk]);
        $translations->shouldAllowMockingProtectedMethods();
        $translations->makePartial();
        $translations
            ->shouldReceive('getStorage')
            ->with($locale)
            ->once()
            ->andReturn($storage);

        self::assertFalse($translations->reset($locale));

        Event::assertNotDispatched(TranslationsUpdated::class);
    }
}
