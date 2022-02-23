<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Document;
use App\Services\DataLoader\Commands\UpdateDocument;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Iterators\ObjectsIterator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\DocumentSync
 */
class DocumentSyncsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $document = Document::factory()->hasEntries(2)->make();

        $this->override(ExceptionHandler::class);

        $this->override(Kernel::class, static function (MockInterface $mock) use ($document): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateDocument::class, [
                    '--no-interaction' => true,
                    'id'               => $document->getKey(),
                ])
                ->once()
                ->andReturn(Command::SUCCESS);
        });

        $this->override(AssetsIteratorImporter::class, static function (MockInterface $mock) use ($document): void {
            $mock
                ->shouldReceive('setIterator')
                ->withArgs(static function (ObjectsIterator $iterator) use ($document): bool {
                    $actual   = iterator_to_array($iterator);
                    $expected = $document->assets->map(new GetKey())->all();

                    return $expected === $actual;
                })
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(false)
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

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeFailed(): void {
        $document  = Document::factory()->hasEntries(2)->make();
        $exception = new Exception();

        $this->override(ExceptionHandler::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('report')
                ->with($exception)
                ->twice()
                ->andReturns();
        });

        $this->override(Kernel::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateDocument::class, Mockery::any())
                ->once()
                ->andThrow($exception);
        });

        $this->override(AssetsIteratorImporter::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('setIterator')
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(false)
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
            'result' => false,
            'assets' => false,
        ];

        $this->assertEquals($expected, $actual);
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

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>
}
