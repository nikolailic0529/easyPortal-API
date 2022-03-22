<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use Closure;
use Error;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\Concerns\ChunkConverter
 */
class ChunkConverterTest extends TestCase {
    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvert(): void {
        $items     = [1, 2, 3, 4, 5];
        $handler   = Mockery::mock(ExceptionHandler::class);
        $converter = new ChunkConverterTest_ChunkConverter($handler);

        self::assertEquals($items, $converter->chunkConvert($items));
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertError(): void {
        $items   = [1, 2, 3, 4, 5];
        $error   = new Exception();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler
            ->shouldReceive('report')
            ->with($error)
            ->once()
            ->andReturns();

        $converter = new ChunkConverterTest_ChunkConverter($handler, static function (int $item) use ($error): int {
            if ($item === 3) {
                throw $error;
            }

            return $item;
        });

        self::assertEquals(
            Arr::except($items, 2),
            $converter->chunkConvert($items),
        );
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertFatalError(): void {
        $items     = [1, 2, 3, 4, 5];
        $error     = new class() extends Exception implements IteratorFatalError {
            // empty
        };
        $handler   = Mockery::mock(ExceptionHandler::class);
        $converter = new ChunkConverterTest_ChunkConverter($handler, static function (int $item) use ($error): int {
            if ($item === 3) {
                throw $error;
            }

            return $item;
        });

        self::expectExceptionObject($error);

        $converter->chunkConvert($items);
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertBrokenIteratorDetection(): void {
        $items   = [1, 2, 3, 4, 5];
        $error   = new Error();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler
            ->shouldReceive('report')
            ->with($error)
            ->times(count($items))
            ->andReturns();

        $converter = new ChunkConverterTest_ChunkConverter($handler, static function () use ($error): int {
            throw $error;
        });

        self::expectExceptionObject(new BrokenIteratorDetected($converter::class));

        $converter->chunkConvert($items);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ChunkConverterTest_ChunkConverter {
    /**
     * @phpstan-use ChunkConverter<mixed, mixed>
     */
    use ChunkConverter {
        chunkConvert as public;
    }

    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected ?Closure $converter = null,
    ) {
        // error
    }

    protected function getConverter(): ?Closure {
        return $this->converter;
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }
}
