<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Document;
use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\DocumentSync
 */
class DocumentSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $document = Document::factory()->hasEntries(2)->create();

        $this->override(ExceptionHandler::class);

        $this->override(DocumentLoader::class, static function (MockInterface $mock) use ($document): void {
            $mock
                ->shouldReceive('setObjectId')
                ->with($document->getKey())
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $this->override(AssetSync::class, static function (MockInterface $mock) use ($document): void {
            $assets = GlobalScopes::callWithoutAll(static function () use ($document): Collection {
                return $document->assets;
            });

            $mock->makePartial();
            $mock
                ->shouldReceive('__invoke')
                ->times(count($assets))
                ->andReturn([
                    'result' => true,
                ]);

            foreach ($assets as $asset) {
                $mock
                    ->shouldReceive('init')
                    ->withArgs(static function (Asset $actual) use ($asset): bool {
                        return $asset->getKey() === $actual->getKey();
                    })
                    ->once()
                    ->andReturnSelf();
            }
        });

        $job      = $this->app->make(DocumentSync::class)->init($document);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => true,
            'assets' => true,
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeSyncAssetsFailed(): void {
        $document  = Document::factory()->hasEntries(1)->create();
        $exception = new Exception();

        $this->override(ExceptionHandler::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('report')
                ->with($exception)
                ->once()
                ->andReturns();
        });

        $this->override(DocumentLoader::class, static function (MockInterface $mock) use ($document): void {
            $mock
                ->shouldReceive('setObjectId')
                ->with($document->getKey())
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $this->override(AssetSync::class, static function (MockInterface $mock) use ($exception): void {
            $mock->makePartial();
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andThrow($exception);
        });

        $job      = $this->app->make(DocumentSync::class)->init($document);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => true,
            'assets' => false,
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeSyncPropertiesFailed(): void {
        $document = Document::factory()->make();
        $job      = Mockery::mock(DocumentSync::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('syncProperties')
            ->once()
            ->andReturn(false);
        $job
            ->shouldReceive('syncAssets')
            ->never();

        $job      = $job->init($document);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => false,
            'assets' => false,
        ];

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>
}
