<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\Translation\TranslationDefaults;
use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

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
        $locale  = 'en_GB';
        $default = 'de_DE';

        $this->app->setLocale($default);

        $this->override(
            TranslationLoader::class,
            static function (MockInterface $mock) use ($default, $locale): void {
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($default)
                    ->once()
                    ->andReturn([
                        'default_loader' => 'string',
                    ]);
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'locale_loader' => 'string',
                    ]);
            },
        );
        $this->override(
            TranslationDefaults::class,
            static function (MockInterface $mock) use ($default, $locale): void {
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($default)
                    ->once()
                    ->andReturn([
                        'default_default' => 'string',
                    ]);
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'locale_default' => 'string',
                    ]);
            },
        );

        $buffer   = new BufferedOutput();
        $kernel   = $this->app->make(Kernel::class);
        $result   = $kernel->call('ep:i18n-locale-export', ['--locale' => $locale], $buffer);
        $actual   = $buffer->fetch();
        $expected = <<<'JSON'
        {
            "default_default": "string",
            "default_loader": "string",
            "locale_default": "string",
            "locale_loader": "string"
        }

        JSON;

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($expected, $actual);
    }
}
