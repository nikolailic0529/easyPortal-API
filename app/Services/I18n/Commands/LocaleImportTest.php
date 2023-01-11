<?php declare(strict_types = 1);

namespace App\Services\I18n\Commands;

use App\Services\I18n\I18n;
use App\Services\I18n\Storages\Spreadsheet;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\I18n\Commands\LocaleImport
 */
class LocaleImportTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:i18n-locale-import');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:i18n-locale-import', $this->app->make(Kernel::class)->all());
    }

    public function testInvoke(): void {
        $locale = 'de_DE';
        $file   = $this->getTestData()->file('.xlsx');

        $this->app->setLocale($locale);

        $this->override(
            I18n::class,
            static function (MockInterface $mock) use ($locale, $file): void {
                $expected = (new Spreadsheet($file))->load();

                self::assertNotEmpty($expected);

                $mock
                    ->shouldReceive('setTranslations')
                    ->with($locale, $expected)
                    ->once()
                    ->andReturn(true);
            },
        );

        $this
            ->artisan(
                'ep:i18n-locale-import',
                [
                    'file'     => $file->getPathname(),
                    '--locale' => $locale,
                ],
            )
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}
