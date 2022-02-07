<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\OemsImporter;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Commands\ImportOems
 */
class ImportOemsTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertArrayHasKey('ep:data-loader-import-oems', $this->app->make(Kernel::class)->all());
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $file = $this->faker->word;

        $this->override(OemsImporter::class, static function (MockInterface $mock) use ($file): void {
            $mock
                ->shouldReceive('import')
                ->with($file)
                ->once()
                ->andReturn();
        });

        $this->app->make(Kernel::class)->call('ep:data-loader-import-oems', [
            'file' => $file,
        ]);
    }
}
