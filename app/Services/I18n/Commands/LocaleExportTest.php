<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\I18n;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\I18n\Commands\LocaleExport
 */
class LocaleExportTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:i18n-locale-export');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:i18n-locale-export', $this->app->make(Kernel::class)->all());
    }

    public function testInvoke(): void {
        $locale = 'de_DE';

        $this->app->setLocale($locale);

        $this->override(
            I18n::class,
            static function (MockInterface $mock) use ($locale): void {
                $mock
                    ->shouldReceive('getTranslations')
                    ->with($locale)
                    ->once()
                    ->andReturn([
                        'string' => 'translations',
                    ]);
            },
        );

        $buffer   = new BufferedOutput();
        $kernel   = $this->app->make(Kernel::class);
        $result   = $kernel->call('ep:i18n-locale-export', [], $buffer);
        $actual   = $buffer->fetch();
        $expected = <<<'JSON'
        {
            "string": "translations"
        }

        JSON;

        self::assertEquals(Command::SUCCESS, $result);
        self::assertEquals($expected, $actual);
    }
}
