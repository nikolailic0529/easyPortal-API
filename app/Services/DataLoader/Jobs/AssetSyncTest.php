<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
use App\Utils\Iterators\ObjectsIterator;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery\MockInterface;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\AssetSync
 */
class AssetSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $asset = Asset::factory()->make();

        $this->override(ExceptionHandler::class);

        $this->override(Client::class, static function (MockInterface $mock) use ($asset): void {
            $mock
                ->shouldReceive('runAssetWarrantyCheck')
                ->with($asset->getKey())
                ->once()
                ->andReturn(true);
        });

        $this->override(AssetsIteratorImporter::class, static function (MockInterface $mock) use ($asset): void {
            $mock
                ->shouldReceive('setIterator')
                ->withArgs(static function (ObjectsIterator $iterator) use ($asset): bool {
                    return [$asset->getKey()] === iterator_to_array($iterator);
                })
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(true)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $job      = $this->app->make(AssetSync::class)->init($asset);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => true,
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeFailed(): void {
        $asset     = Asset::factory()->make();
        $exception = new Exception();

        $this->override(ExceptionHandler::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('report')
                ->with($exception)
                ->once()
                ->andReturns();
        });

        $this->override(Client::class, static function (MockInterface $mock) use ($asset, $exception): void {
            $mock
                ->shouldReceive('runAssetWarrantyCheck')
                ->with($asset->getKey())
                ->once()
                ->andThrow($exception);
        });

        $this->override(AssetsIteratorImporter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(true)
                ->once()
                ->andReturnSelf();
        });

        $job      = $this->app->make(AssetSync::class)->init($asset);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => false,
        ];

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>
}
