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
 * @covers \App\Services\Search\Processors\FulltextProcessor
 */
class FulltextProcessorTest extends TestCase {
    use WithQueryLogs;

    public function testProcess(): void {
        $log        = [];
        $config     = $this->app->make(Repository::class);
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

        $processor
            ->setModel(FulltextProcessorTest_Model::class)
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
class FulltextProcessorTest_Model extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $table = 'testing__search__fulltext_processors';
}
