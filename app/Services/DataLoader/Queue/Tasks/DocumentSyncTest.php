<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Tasks;

use App\Models\Document;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Processors\Loader\Loaders\DocumentLoader;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Tasks\DocumentSync
 */
class DocumentSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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

        $this->override(IteratorImporter::class, static function (MockInterface $mock): void {
            $mock->makePartial();
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $job      = $this->app->make(DocumentSync::class)->init($document);
        $actual   = $this->app->call($job);
        $expected = [
            'result' => true,
            'assets' => true,
        ];

        self::assertEquals($expected, $actual);
    }

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

        $this->override(IteratorImporter::class, static function (MockInterface $mock) use ($exception): void {
            $mock->makePartial();
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
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
