<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;
use Tests\WithQueryLogs;

use function array_map;
use function array_merge;

/**
 * @internal
 * @covers \App\Services\Search\Processors\FulltextsProcessor
 */
class FulltextsProcessorTest extends TestCase {
    use WithQueryLogs;

    public function testProcess(): void {
        $log = [];

        $this->override(FulltextProcessor::class, static function () use (&$log): FulltextProcessor {
            $config = Mockery::mock(Repository::class);
            $config
                ->shouldReceive('get')
                ->with('ep.telescope.processor.limit', -1)
                ->andReturn(null);

            $handler    = Mockery::mock(ExceptionHandler::class);
            $dispatcher = Mockery::mock(Dispatcher::class);
            $processor  = Mockery::mock(FulltextProcessor::class, [$handler, $dispatcher, $config]);
            $processor->shouldAllowMockingProtectedMethods();
            $processor->makePartial();
            $processor
                ->shouldReceive('execute')
                ->atLeast()
                ->once()
                ->andReturnUsing(static function (Model $model, array $queries) use (&$log): void {
                    $log = array_merge($log, array_map(
                        static function (string $query): array {
                            return [
                                'query'    => $query,
                                'bindings' => [],
                            ];
                        },
                        $queries,
                    ));
                });

            return $processor;
        });

        $this->app->make(FulltextsProcessor::class)
            ->setModels([FulltextsProcessorTest_Model::class])
            ->start();

        self::assertQueryLogEquals('~queries.json', $log);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class FulltextsProcessorTest_Model extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $table = 'testing__search__fulltext_processors';
}
