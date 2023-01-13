<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Tasks;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
use App\Utils\Iterators\ObjectsIterator;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery\MockInterface;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Tasks\AssetSync
 */
class AssetSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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

        $this->override(IteratorImporter::class, static function (MockInterface $mock) use ($asset): void {
            $mock
                ->shouldReceive('setIterator')
                ->withArgs(static function (ObjectsIterator $iterator) use ($asset): bool {
                    return [$asset->getKey()] === iterator_to_array($iterator);
                })
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
            'warranty' => true,
            'result'   => true,
        ];

        self::assertEquals($expected, $actual);
    }

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

        $this->override(Client::class, static function (MockInterface $mock) use ($asset): void {
            $mock
                ->shouldReceive('runAssetWarrantyCheck')
                ->with($asset->getKey())
                ->once()
                ->andReturn(true);
        });

        $this->override(IteratorImporter::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andThrow($exception);
        });

        $job      = $this->app->make(AssetSync::class)->init($asset);
        $actual   = $this->app->call($job);
        $expected = [
            'warranty' => true,
            'result'   => false,
        ];

        self::assertEquals($expected, $actual);
    }

    public function testInvokeWarrantyFailed(): void {
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

        $this->override(IteratorImporter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setIterator')
                ->never();
        });

        $job      = $this->app->make(AssetSync::class)->init($asset);
        $actual   = $this->app->call($job);
        $expected = [
            'warranty' => false,
            'result'   => false,
        ];

        self::assertEquals($expected, $actual);
    }

    public function testInvokeWarrantyNoSkuOrSerialNumber(): void {
        $this->override(ExceptionHandler::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('report')
                ->never();
        });

        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('runAssetWarrantyCheck')
                ->never();
        });

        $this->override(IteratorImporter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $asset = Asset::factory()->make();
        $asset->setAttribute(
            $this->faker->boolean() ? 'serial_number' : 'product_id',
            null,
        );
        $asset->save();

        $job      = $this->app->make(AssetSync::class)->init($asset);
        $actual   = $this->app->call($job);
        $expected = [
            'warranty' => false,
            'result'   => true,
        ];

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>
}
