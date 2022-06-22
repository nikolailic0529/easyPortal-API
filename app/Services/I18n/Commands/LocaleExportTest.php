<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

use function explode;

/**
 * @internal
 * @coversDefaultClass \App\Services\I18n\Commands\LocaleExport
 */
class LocaleExportTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:i18n-locale-export', $this->app->make(Kernel::class)->all());
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeAppLocale(): void {
        $locale = 'de_DE';

        $this->app->setLocale($locale);

        $this->override(
            TranslationLoader::class,
            static function (MockInterface $mock) use ($locale): void {
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'loader' => 'string',
                    ]);
            },
        );
        $this->override(
            TranslationDefaults::class,
            static function (MockInterface $mock) use ($locale): void {
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'default' => 'string',
                    ]);
            },
        );

        $buffer   = new BufferedOutput();
        $kernel   = $this->app->make(Kernel::class);
        $result   = $kernel->call('ep:i18n-locale-export', [], $buffer);
        $actual   = $buffer->fetch();
        $expected = <<<'JSON'
        {
            "default": "string",
            "loader": "string"
        }

        JSON;

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeAnotherLocale(): void {
        $locale   = 'en_GB';
        $default  = 'de_DE';
        $fallback = 'it_IT';

        $this->app->setLocale($default);
        $this->app->setFallbackLocale($fallback);

        $this->override(
            TranslationLoader::class,
            static function (MockInterface $mock) use ($locale, $fallback): void {
                $mock->shouldAllowMockingProtectedMethods();
                $mock->makePartial();
                $mock
                    ->shouldReceive('getFallbackLocale')
                    ->once()
                    ->andReturn($fallback);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with(explode('_', $locale)[0])
                    ->once()
                    ->andReturn([
                        'loader_locale_json_base' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with(explode('_', $locale)[0])
                    ->once()
                    ->andReturn([
                        'loader_locale_storage_base' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'loader_locale_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'loader_locale_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with($fallback)
                    ->once()
                    ->andReturn([
                        'loader_fallback_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with($fallback)
                    ->once()
                    ->andReturn([
                        'loader_fallback_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with(explode('_', $fallback)[0])
                    ->once()
                    ->andReturn([
                        'loader_fallback_base_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with(explode('_', $fallback)[0])
                    ->once()
                    ->andReturn([
                        'loader_fallback_base_storage' => 'string',
                    ]);
            },
        );
        $this->override(
            TranslationDefaults::class,
            static function (MockInterface $mock) use ($locale, $fallback): void {
                $mock->shouldAllowMockingProtectedMethods();
                $mock->makePartial();
                $mock
                    ->shouldReceive('getFallbackLocale')
                    ->once()
                    ->andReturn($fallback);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'default_locale_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'default_locale_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadModels')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'default_locale_models' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with(explode('_', $locale)[0])
                    ->once()
                    ->andReturn([
                        'default_locale_base_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with(explode('_', $locale)[0])
                    ->once()
                    ->andReturn([
                        'default_locale_base_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadModels')
                    ->with(explode('_', $locale)[0])
                    ->once()
                    ->andReturn([
                        'default_locale_base_models' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with($fallback)
                    ->once()
                    ->andReturn([
                        'default_fallback_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with($fallback)
                    ->once()
                    ->andReturn([
                        'default_fallback_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadModels')
                    ->with($fallback)
                    ->once()
                    ->andReturn([
                        'default_fallback_models' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadJsonPaths')
                    ->with(explode('_', $fallback)[0])
                    ->once()
                    ->andReturn([
                        'default_fallback_base_json' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadStorage')
                    ->with(explode('_', $fallback)[0])
                    ->once()
                    ->andReturn([
                        'default_fallback_base_storage' => 'string',
                    ]);
                $mock
                    ->shouldReceive('loadModels')
                    ->with(explode('_', $fallback)[0])
                    ->once()
                    ->andReturn([
                        'default_fallback_base_models' => 'string',
                    ]);
            },
        );

        $buffer   = new BufferedOutput();
        $kernel   = $this->app->make(Kernel::class);
        $result   = $kernel->call('ep:i18n-locale-export', ['--locale' => $locale], $buffer);
        $actual   = $buffer->fetch();
        $expected = <<<'JSON'
        {
            "default_fallback_base_json": "string",
            "default_fallback_base_models": "string",
            "default_fallback_base_storage": "string",
            "default_fallback_json": "string",
            "default_fallback_models": "string",
            "default_fallback_storage": "string",
            "default_locale_base_json": "string",
            "default_locale_base_models": "string",
            "default_locale_base_storage": "string",
            "default_locale_json": "string",
            "default_locale_models": "string",
            "default_locale_storage": "string",
            "loader_fallback_base_json": "string",
            "loader_fallback_base_storage": "string",
            "loader_fallback_json": "string",
            "loader_fallback_storage": "string",
            "loader_locale_json": "string",
            "loader_locale_json_base": "string",
            "loader_locale_storage": "string",
            "loader_locale_storage_base": "string"
        }

        JSON;

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($expected, $actual);
    }
}
