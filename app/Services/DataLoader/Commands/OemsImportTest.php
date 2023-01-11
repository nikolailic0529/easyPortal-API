<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Importer\Importers\OemsImporter;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Commands\OemsImport
 */
class OemsImportTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:data-loader-oems-import');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:data-loader-oems-import', $this->app->make(Kernel::class)->all());
    }

    public function testInvoke(): void {
        $file = $this->faker->word();

        $this->override(OemsImporter::class, static function (MockInterface $mock) use ($file): void {
            $mock
                ->shouldReceive('import')
                ->with($file)
                ->once()
                ->andReturn();
        });

        $this->app->make(Kernel::class)->call('ep:data-loader-oems-import', [
            'file' => $file,
        ]);
    }
}
